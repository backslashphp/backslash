<?php

declare(strict_types=1);

namespace Backslash\TamarackDbEventStore;

use Backslash\EventNameResolver\MatchingClassEventNameResolverAdapter;
use Backslash\EventStore\Query\EventClass;
use Backslash\EventStore\Query\Identifier;
use Backslash\EventStore\Query\Metadata as MetadataQuery;
use Backslash\EventStore\Query\Query;
use Backslash\Shared\Event\StudentNameChangedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueryToTamarackDbQueryTest extends TestCase
{
    private MatchingClassEventNameResolverAdapter $eventNameResolver;

    public function setUp(): void
    {
        parent::setUp();
        $this->eventNameResolver = new MatchingClassEventNameResolverAdapter();
    }

    #[Test]
    public function it_translates_a_match_all_query_to_a_wildcard(): void
    {
        $payload = (new QueryToTamarackDbQuery(new Query(), $this->eventNameResolver))->toPayload();
        $this->assertSame('*', $payload);
    }

    #[Test]
    public function it_combines_filters_of_a_single_item_with_and(): void
    {
        $query = (new Query())->withItem(
            EventClass::in(StudentRegisteredEvent::class),
            Identifier::is('studentId', '1'),
            MetadataQuery::is('foo', 'a'),
        );

        $payload = (new QueryToTamarackDbQuery($query, $this->eventNameResolver))->toPayload();

        $this->assertSame([
            [
                'types' => [StudentRegisteredEvent::class],
                'identifiers' => [['name' => 'studentId', 'value' => '1']],
                'metadata' => [['name' => 'foo', 'value' => 'a']],
            ],
        ], $payload);
    }

    #[Test]
    public function it_combines_items_with_or(): void
    {
        $query = (new Query())
            ->withItem(Identifier::is('studentId', '1'))
            ->withItem(Identifier::is('studentId', '2'));

        $payload = (new QueryToTamarackDbQuery($query, $this->eventNameResolver))->toPayload();

        $this->assertSame([
            ['identifiers' => [['name' => 'studentId', 'value' => '1']]],
            ['identifiers' => [['name' => 'studentId', 'value' => '2']]],
        ], $payload);
    }

    #[Test]
    public function it_supports_multiple_event_classes_in_a_single_item(): void
    {
        $query = (new Query())->withItem(EventClass::in(StudentRegisteredEvent::class, StudentNameChangedEvent::class));

        $payload = (new QueryToTamarackDbQuery($query, $this->eventNameResolver))->toPayload();

        $this->assertSame([
            ['types' => [StudentRegisteredEvent::class, StudentNameChangedEvent::class]],
        ], $payload);
    }
}
