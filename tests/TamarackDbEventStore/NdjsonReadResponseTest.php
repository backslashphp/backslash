<?php

declare(strict_types=1);

namespace Backslash\TamarackDbEventStore;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NdjsonReadResponseTest extends TestCase
{
    #[Test]
    public function it_parses_events_followed_by_a_trailing_has_more_line(): void
    {
        $body = implode("\n", [
            json_encode(['sequence' => 1, 'time' => '2026-01-01T00:00:00Z', 'type' => 'foo', 'identifiers' => [], 'metadata' => [], 'payload' => '{}']),
            json_encode(['hasMore' => false]),
        ]) . "\n";

        $result = (new NdjsonReadResponse($body))->toEvents();

        $this->assertFalse($result['hasMore']);
        $this->assertCount(1, $result['events']);
        $this->assertSame(1, $result['events'][0]['sequence']);
    }

    #[Test]
    public function it_reports_has_more_true_when_the_trailer_says_so(): void
    {
        $body = implode("\n", [
            json_encode(['sequence' => 1, 'time' => '2026-01-01T00:00:00Z', 'type' => 'foo', 'identifiers' => [], 'metadata' => [], 'payload' => '{}']),
            json_encode(['sequence' => 2, 'time' => '2026-01-01T00:00:01Z', 'type' => 'bar', 'identifiers' => [], 'metadata' => [], 'payload' => '{}']),
            json_encode(['hasMore' => true]),
        ]);

        $result = (new NdjsonReadResponse($body))->toEvents();

        $this->assertTrue($result['hasMore']);
        $this->assertCount(2, $result['events']);
    }

    #[Test]
    public function it_handles_an_empty_page(): void
    {
        $body = json_encode(['hasMore' => false]);

        $result = (new NdjsonReadResponse($body))->toEvents();

        $this->assertFalse($result['hasMore']);
        $this->assertSame([], $result['events']);
    }

    #[Test]
    public function it_throws_when_the_page_is_cut_short_without_a_trailer(): void
    {
        $body = json_encode(['sequence' => 1, 'time' => '2026-01-01T00:00:00Z', 'type' => 'foo', 'identifiers' => [], 'metadata' => [], 'payload' => '{}']);

        $this->expectException(TamarackDbEventStoreException::class);

        (new NdjsonReadResponse($body))->toEvents();
    }
}
