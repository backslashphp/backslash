<?php

declare(strict_types=1);

namespace Backslash\PdoTransactionRepositoryMiddleware;

use Backslash\EventStore\EventStore;
use Backslash\Repository\DefaultCoreStrategy;
use Backslash\Repository\Repository;
use Backslash\Shared\EventStore\TestAdapter;
use Backslash\Shared\Model\StudentRegistrationModel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PdoTransactionRepositoryMiddlewareTest extends TestCase
{
    #[Test]
    public function it_begins_and_commits_transaction_on_successful_store_changes(): void
    {
        $pdo = new TestPdo();
        $repository = new Repository(new DefaultCoreStrategy(new EventStore(new TestAdapter()), new CascadingTestEventBus()));
        $repository->addMiddleware(new PdoTransactionRepositoryMiddleware($pdo));

        $model = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('1'));
        $model->register('1', 'John');
        $repository->storeChanges($model);

        $this->assertEquals(['beginTransaction', 'commit'], $pdo->getCalls());
        $this->assertFalse($pdo->inTransaction());
    }

    #[Test]
    public function it_begins_and_rolls_back_transaction_on_exception(): void
    {
        $pdo = new TestPdo();
        $eventBus = new CascadingTestEventBus();
        $eventBus->onPublish = function (): void {
            throw new TestException('Something went wrong');
        };
        $repository = new Repository(new DefaultCoreStrategy(new EventStore(new TestAdapter()), $eventBus));
        $repository->addMiddleware(new PdoTransactionRepositoryMiddleware($pdo));

        $model = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('1'));
        $model->register('1', 'John');

        try {
            $repository->storeChanges($model);
            $this->fail('Expected exception was not thrown');
        } catch (TestException $e) {
            $this->assertEquals('Something went wrong', $e->getMessage());
        }

        $this->assertEquals(['beginTransaction', 'rollBack'], $pdo->getCalls());
        $this->assertFalse($pdo->inTransaction());
    }

    #[Test]
    public function it_reuses_the_transaction_for_a_store_changes_nested_via_publish(): void
    {
        $pdo = new TestPdo();
        $eventBus = new CascadingTestEventBus();
        $repository = new Repository(new DefaultCoreStrategy(new EventStore(new TestAdapter()), $eventBus));
        $repository->addMiddleware(new PdoTransactionRepositoryMiddleware($pdo));

        $modelA = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('1'));
        $modelA->register('1', 'John');

        $modelB = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('2'));
        $modelB->register('2', 'Jane');

        $triggered = false;
        $eventBus->onPublish = function () use ($repository, $modelB, &$triggered): void {
            if (!$triggered) {
                $triggered = true;
                // Simulates a processor reacting synchronously to the published event
                // by storing changes on another model, while the first storeChanges()
                // transaction is still open.
                $repository->storeChanges($modelB);
            }
        };

        $repository->storeChanges($modelA);

        // A single begin/commit pair, even though storeChanges() was called twice.
        $this->assertEquals(['beginTransaction', 'commit'], $pdo->getCalls());
    }

    #[Test]
    public function it_opens_a_separate_transaction_for_independent_store_changes(): void
    {
        $pdo = new TestPdo();
        $repository = new Repository(new DefaultCoreStrategy(new EventStore(new TestAdapter()), new CascadingTestEventBus()));
        $repository->addMiddleware(new PdoTransactionRepositoryMiddleware($pdo));

        $modelA = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('1'));
        $modelA->register('1', 'John');
        $repository->storeChanges($modelA);

        $modelB = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('2'));
        $modelB->register('2', 'Jane');
        $repository->storeChanges($modelB);

        // Two independent (non-nested) storeChanges() calls commit separately.
        $this->assertEquals(['beginTransaction', 'commit', 'beginTransaction', 'commit'], $pdo->getCalls());
    }

    #[Test]
    public function it_rolls_back_and_resets_nested_levels_when_the_nested_store_changes_fails(): void
    {
        $pdo = new TestPdo();
        $eventBus = new CascadingTestEventBus();
        $repository = new Repository(new DefaultCoreStrategy(new EventStore(new TestAdapter()), $eventBus));
        $repository->addMiddleware(new PdoTransactionRepositoryMiddleware($pdo));

        $modelA = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('1'));
        $modelA->register('1', 'John');

        $modelB = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('2'));
        $modelB->register('2', 'Jane');

        $triggered = false;
        $eventBus->onPublish = function () use ($repository, $modelB, &$triggered): void {
            if (!$triggered) {
                $triggered = true;
                $repository->storeChanges($modelB);
            } else {
                throw new TestException('Nested failure');
            }
        };

        try {
            $repository->storeChanges($modelA);
            $this->fail('Expected exception was not thrown');
        } catch (TestException $e) {
            $this->assertEquals('Nested failure', $e->getMessage());
        }

        $this->assertEquals(['beginTransaction', 'rollBack'], $pdo->getCalls());
        $this->assertFalse($pdo->inTransaction());

        // nestedLevels must have been reset to 0: a subsequent, independent
        // storeChanges() should start its own fresh transaction.
        $eventBus->onPublish = null;
        $modelC = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('3'));
        $modelC->register('3', 'Bill');
        $repository->storeChanges($modelC);

        $this->assertEquals(
            ['beginTransaction', 'rollBack', 'beginTransaction', 'commit'],
            $pdo->getCalls(),
        );
    }

    #[Test]
    public function it_acquires_and_releases_a_lock_around_the_transaction_on_mysql(): void
    {
        $pdo = new TestPdo(mysql: true);
        $repository = new Repository(new DefaultCoreStrategy(new EventStore(new TestAdapter()), new CascadingTestEventBus()));
        $repository->addMiddleware(new PdoTransactionRepositoryMiddleware($pdo));

        $model = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('1'));
        $model->register('1', 'John');
        $repository->storeChanges($model);

        $this->assertEquals(
            ['GET_LOCK(backslash)', 'beginTransaction', 'commit', 'RELEASE_LOCK(backslash)'],
            $pdo->getCalls(),
        );
    }

    #[Test]
    public function it_releases_the_lock_after_rollback_on_mysql(): void
    {
        $pdo = new TestPdo(mysql: true);
        $eventBus = new CascadingTestEventBus();
        $eventBus->onPublish = function (): void {
            throw new TestException('Something went wrong');
        };
        $repository = new Repository(new DefaultCoreStrategy(new EventStore(new TestAdapter()), $eventBus));
        $repository->addMiddleware(new PdoTransactionRepositoryMiddleware($pdo));

        $model = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('1'));
        $model->register('1', 'John');

        try {
            $repository->storeChanges($model);
            $this->fail('Expected exception was not thrown');
        } catch (TestException $e) {
            $this->assertEquals('Something went wrong', $e->getMessage());
        }

        $this->assertEquals(
            ['GET_LOCK(backslash)', 'beginTransaction', 'rollBack', 'RELEASE_LOCK(backslash)'],
            $pdo->getCalls(),
        );
    }

    #[Test]
    public function it_does_not_acquire_a_lock_on_nested_store_changes_on_mysql(): void
    {
        $pdo = new TestPdo(mysql: true);
        $eventBus = new CascadingTestEventBus();
        $repository = new Repository(new DefaultCoreStrategy(new EventStore(new TestAdapter()), $eventBus));
        $repository->addMiddleware(new PdoTransactionRepositoryMiddleware($pdo));

        $modelA = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('1'));
        $modelA->register('1', 'John');

        $modelB = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('2'));
        $modelB->register('2', 'Jane');

        $triggered = false;
        $eventBus->onPublish = function () use ($repository, $modelB, &$triggered): void {
            if (!$triggered) {
                $triggered = true;
                $repository->storeChanges($modelB);
            }
        };

        $repository->storeChanges($modelA);

        // A single GET_LOCK/RELEASE_LOCK pair, even though storeChanges() was called twice.
        $this->assertEquals(
            ['GET_LOCK(backslash)', 'beginTransaction', 'commit', 'RELEASE_LOCK(backslash)'],
            $pdo->getCalls(),
        );
    }

    #[Test]
    public function it_throws_when_the_lock_cannot_be_acquired_on_mysql(): void
    {
        $pdo = new TestPdo(mysql: true, getLockResult: 0);
        $repository = new Repository(new DefaultCoreStrategy(new EventStore(new TestAdapter()), new CascadingTestEventBus()));
        $repository->addMiddleware(new PdoTransactionRepositoryMiddleware($pdo));

        $model = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('1'));
        $model->register('1', 'John');

        try {
            $repository->storeChanges($model);
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('Unable to acquire the "backslash" lock.', $e->getMessage());
        }

        $this->assertEquals(['GET_LOCK(backslash)'], $pdo->getCalls());
        $this->assertFalse($pdo->inTransaction());
    }
}
