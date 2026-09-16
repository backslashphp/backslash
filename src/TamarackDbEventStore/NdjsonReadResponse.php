<?php

declare(strict_types=1);

namespace Backslash\TamarackDbEventStore;

final class NdjsonReadResponse
{
    private string $body;

    public function __construct(string $body)
    {
        $this->body = $body;
    }

    /** @return array{hasMore: bool, events: array[]} */
    public function toEvents(): array
    {
        $events = [];
        $hasMore = null;

        foreach (explode("\n", rtrim($this->body, "\n")) as $line) {
            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (is_array($decoded) && array_key_exists('hasMore', $decoded)) {
                $hasMore = (bool) $decoded['hasMore'];
                continue;
            }

            $events[] = $decoded;
        }

        if ($hasMore === null) {
            throw new TamarackDbEventStoreException(
                'TamarackDB response ended before the page finished (no trailing hasMore line); retry the read.',
            );
        }

        return ['hasMore' => $hasMore, 'events' => $events];
    }
}
