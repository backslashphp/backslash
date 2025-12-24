<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\EventBus\EventBus;
use Backslash\EventStore\EventStore;
use Backslash\EventStore\Query\Identifier;
use Backslash\Model\AbstractModel;
use Backslash\Repository\RepositoryInterface;
use Backslash\Shared\Event\StudentNameChangedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\PdoEventStore\InMemorySqlitePdoEventStoreFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GivenWhenThenTest extends TestCase
{
    private Scenario $scenario;

    public function setUp(): void
    {
        parent::setUp();
        $this->scenario = new Scenario(
            eventStore: new EventStore(InMemorySqlitePdoEventStoreFactory::build()),
        );
    }

    #[Test]
    public function it_uses_given_when_then_syntax(): void
    {
        $this->scenario->play(
            new Play()
                ->given(
                    new StudentRegisteredEvent('1', 'John'),
                )
                ->when(function (RepositoryInterface $repo): void {
                    $model = $repo->loadModel(
                        TestStudentModel::class,
                        Identifier::is('studentId', '1'),
                    );
                    $model->changeName('Jane');
                    $repo->storeChanges($model);
                })
                ->then(
                    fn (PublishedEvents $events) =>
                    $this->assertCount(1, $events->getAll()),
                )
                ->then(
                    fn (PublishedEvents $events) =>
                    $this->assertNotEmpty($events->getAllOf(StudentNameChangedEvent::class)),
                ),
        );
    }

    #[Test]
    public function it_detects_projections_are_not_needed_automatically(): void
    {
        $handlerCalled = 0;

        $eventBus = new EventBus();
        $eventBus->subscribe(StudentNameChangedEvent::class, new class ($handlerCalled) implements \Backslash\EventBus\EventHandlerInterface {
            public function __construct(private int &$count)
            {
            }
            public function handle(object $event): void
            {
                $this->count++;
            }
        });

        $scenario = new Scenario(
            eventBus: $eventBus,
            eventStore: new EventStore(InMemorySqlitePdoEventStoreFactory::build()),
        );

        // Enable DETECT mode to automatically determine if projections are needed
        $scenario->setEventPublishingMode(EventPublishingMode::DETECT);

        $scenario->play(
            new Play()
                ->given(new StudentRegisteredEvent('1', 'John'))
                ->when(function (RepositoryInterface $repo): void {
                    $model = $repo->loadModel(
                        TestStudentModel::class,
                        Identifier::is('studentId', '1'),
                    );
                    $model->changeName('Jane');
                    $repo->storeChanges($model);
                })
                ->then(
                    fn (PublishedEvents $events) =>
                    $this->assertCount(1, $events->getAll()),
                ),
        );

        // Projections not updated because no then(UpdatedProjections) was used
        $this->assertEquals(0, $handlerCalled);
    }

    #[Test]
    public function it_detects_projections_are_needed_automatically(): void
    {
        $handlerCalled = 0;

        $eventBus = new EventBus();
        $eventBus->subscribe(StudentNameChangedEvent::class, new class ($handlerCalled) implements \Backslash\EventBus\EventHandlerInterface {
            public function __construct(private int &$count)
            {
            }
            public function handle(object $event): void
            {
                $this->count++;
            }
        });

        $scenario = new Scenario(
            eventBus: $eventBus,
            eventStore: new EventStore(InMemorySqlitePdoEventStoreFactory::build()),
        );

        // Enable DETECT mode to automatically determine if projections are needed
        $scenario->setEventPublishingMode(EventPublishingMode::DETECT);

        $scenario->play(
            new Play()
                ->given(new StudentRegisteredEvent('1', 'John'))
                ->when(function (RepositoryInterface $repo): void {
                    $model = $repo->loadModel(
                        TestStudentModel::class,
                        Identifier::is('studentId', '1'),
                    );
                    $model->changeName('Jane');
                    $repo->storeChanges($model);
                })
                ->then(
                    fn (UpdatedProjections $projections) =>
                    $this->assertTrue(true),
                ),
        );

        // Projections updated because then(UpdatedProjections) was used
        $this->assertEquals(1, $handlerCalled); // Only when, not given
    }

    #[Test]
    public function it_supports_with_projections_flag(): void
    {
        $handlerCalled = 0;

        $eventBus = new EventBus();
        // Subscribe to both events to count both given and when
        $eventBus->subscribe(StudentRegisteredEvent::class, new class ($handlerCalled) implements \Backslash\EventBus\EventHandlerInterface {
            public function __construct(private int &$count)
            {
            }
            public function handle(object $event): void
            {
                $this->count++;
            }
        });
        $eventBus->subscribe(StudentNameChangedEvent::class, new class ($handlerCalled) implements \Backslash\EventBus\EventHandlerInterface {
            public function __construct(private int &$count)
            {
            }
            public function handle(object $event): void
            {
                $this->count++;
            }
        });

        $scenario = new Scenario(
            eventBus: $eventBus,
            eventStore: new EventStore(InMemorySqlitePdoEventStoreFactory::build()),
        );

        // Force projections ON
        $scenario->setEventPublishingMode(EventPublishingMode::ALWAYS);

        $scenario->play(
            new Play()
                ->given(new StudentRegisteredEvent('1', 'John'))
                ->when(function (RepositoryInterface $repo): void {
                    $model = $repo->loadModel(
                        TestStudentModel::class,
                        Identifier::is('studentId', '1'),
                    );
                    $model->changeName('Jane');
                    $repo->storeChanges($model);
                })
                ->then(
                    fn (PublishedEvents $events) =>
                    $this->assertCount(1, $events->getAll()),
                ),
        );

        // Projections updated because ALWAYS mode was set
        $this->assertEquals(2, $handlerCalled); // 1 for StudentRegistered in given + 1 for StudentNameChanged in when
    }

    #[Test]
    public function it_supports_without_projections_flag(): void
    {
        $handlerCalled = 0;

        $eventBus = new EventBus();
        $eventBus->subscribe(StudentNameChangedEvent::class, new class ($handlerCalled) implements \Backslash\EventBus\EventHandlerInterface {
            public function __construct(private int &$count)
            {
            }
            public function handle(object $event): void
            {
                $this->count++;
            }
        });

        $scenario = new Scenario(
            eventBus: $eventBus,
            eventStore: new EventStore(InMemorySqlitePdoEventStoreFactory::build()),
        );

        // Force projections OFF
        $scenario->setEventPublishingMode(EventPublishingMode::NEVER);

        $scenario->play(
            new Play()
                ->given(new StudentRegisteredEvent('1', 'John'))
                ->when(function (RepositoryInterface $repo): void {
                    $model = $repo->loadModel(
                        TestStudentModel::class,
                        Identifier::is('studentId', '1'),
                    );
                    $model->changeName('Jane');
                    $repo->storeChanges($model);
                })
                ->then(
                    fn (UpdatedProjections $projections) =>  // Even though we use UpdatedProjections
                    $this->assertTrue(true),
                ),
        );

        // Projections NOT updated because NEVER mode overrides detection
        $this->assertEquals(0, $handlerCalled);
    }

    #[Test]
    public function it_supports_when_with_repository_closure(): void
    {
        $this->scenario->play(
            new Play()
                ->given(new StudentRegisteredEvent('1', 'John'))
                ->when(function (RepositoryInterface $repo): void {
                    $model = $repo->loadModel(
                        TestStudentModel::class,
                        Identifier::is('studentId', '1'),
                    );
                    $model->changeName('Jane');
                    $repo->storeChanges($model);
                })
                ->then(
                    fn (PublishedEvents $events) =>
                    $this->assertNotEmpty($events->getAllOf(StudentNameChangedEvent::class)),
                ),
        );
    }

    #[Test]
    public function it_resets_blocking_state_between_plays(): void
    {
        $handlerCalled = 0;

        $eventBus = new EventBus();
        $eventBus->subscribe(StudentRegisteredEvent::class, new class ($handlerCalled) implements \Backslash\EventBus\EventHandlerInterface {
            public function __construct(private int &$count)
            {
            }
            public function handle(object $event): void
            {
                $this->count++;
            }
        });

        $scenario = new Scenario(
            eventBus: $eventBus,
            eventStore: new EventStore(InMemorySqlitePdoEventStoreFactory::build()),
        );

        // Set to ALWAYS mode to ensure events are published in both plays
        $scenario->setEventPublishingMode(EventPublishingMode::ALWAYS);

        // Play 1: publishes events in GIVEN
        // Play 2: should also publish events in GIVEN (verifying state is reset)
        $scenario->play(
            new Play()
                ->given(new StudentRegisteredEvent('1', 'John'))
                ->when(function (RepositoryInterface $repo): void {
                    // Do nothing
                })
                ->then(
                    fn (PublishedEvents $events) =>
                    $this->assertCount(0, $events->getAll()),
                ),
            new Play()
                ->given(new StudentRegisteredEvent('2', 'Jane'))
                ->then(
                    fn (PublishedEvents $events) =>
                    $this->assertTrue(true),
                ),
        );

        // Should be 2 (one from each play's GIVEN phase)
        // If blocking state isn't reset properly, it might be different
        $this->assertEquals(2, $handlerCalled, 'Both plays should have events published during GIVEN');
    }
}

// Test model
class TestStudentModel extends AbstractModel
{
    private string $studentId;
    private string $name;

    public function changeName(string $newName): void
    {
        $this->record(new StudentNameChangedEvent($this->studentId, $this->name, $newName));
    }

    protected function applyStudentRegisteredEvent(StudentRegisteredEvent $event): void
    {
        $this->studentId = $event->getStudentId();
        $this->name = $event->getName();
    }

    protected function applyStudentNameChangedEvent(StudentNameChangedEvent $event): void
    {
        $this->name = $event->getNew();
    }
}
