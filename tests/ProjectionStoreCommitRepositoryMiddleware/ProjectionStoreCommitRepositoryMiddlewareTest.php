<?php

declare(strict_types=1);

namespace Backslash\ProjectionStoreCommitRepositoryMiddleware;

use Backslash\EventStore\EventStore;
use Backslash\ProjectionStore\ProjectionStore;
use Backslash\Repository\Repository;
use Backslash\Shared\EventStore\TestAdapter;
use Backslash\Shared\Model\StudentRegistrationModel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProjectionStoreCommitRepositoryMiddlewareTest extends TestCase
{
    #[Test]
    public function it_commits_projection_store_on_successful_store_changes(): void
    {
        $adapter = new TestProjectionStore();
        $projectionStore = new ProjectionStore($adapter);
        $repository = new Repository(new EventStore(new TestAdapter()), new CascadingTestEventBus());
        $repository->addMiddleware(new ProjectionStoreCommitRepositoryMiddleware($projectionStore));

        $model = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('1'));
        $model->register('1', 'John');
        $repository->storeChanges($model);

        $this->assertEquals([['commit']], $adapter->getCalls());
    }

    #[Test]
    public function it_rolls_back_projection_store_on_exception(): void
    {
        $adapter = new TestProjectionStore();
        $projectionStore = new ProjectionStore($adapter);
        $eventBus = new CascadingTestEventBus();
        $eventBus->onPublish = function (): void {
            throw new TestException('Something went wrong');
        };
        $repository = new Repository(new EventStore(new TestAdapter()), $eventBus);
        $repository->addMiddleware(new ProjectionStoreCommitRepositoryMiddleware($projectionStore));

        $model = $repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery('1'));
        $model->register('1', 'John');

        try {
            $repository->storeChanges($model);
            $this->fail('Expected exception was not thrown');
        } catch (TestException $e) {
            $this->assertEquals('Something went wrong', $e->getMessage());
        }

        // rollback() doesn't propagate to the adapter, it just resets the UnitOfWork.
        $this->assertEquals([], $adapter->getCalls());
    }

    #[Test]
    public function it_commits_only_once_for_a_store_changes_nested_via_publish(): void
    {
        $adapter = new TestProjectionStore();
        $projectionStore = new ProjectionStore($adapter);
        $eventBus = new CascadingTestEventBus();
        $repository = new Repository(new EventStore(new TestAdapter()), $eventBus);
        $repository->addMiddleware(new ProjectionStoreCommitRepositoryMiddleware($projectionStore));

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

        $this->assertEquals([['commit']], $adapter->getCalls());
    }
}
