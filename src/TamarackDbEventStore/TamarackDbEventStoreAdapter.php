<?php

declare(strict_types=1);

namespace Backslash\TamarackDbEventStore;

use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventNameResolver\EventNameResolverInterface;
use Backslash\EventStore\AdapterInterface;
use Backslash\EventStore\ConcurrencyException;
use Backslash\EventStore\InspectorInterface;
use Backslash\EventStore\Query\Query;
use Backslash\EventStore\StoredRecordedEventStream;
use Backslash\Serializer\SerializerInterface;
use DateTimeImmutable;
use Generator;

final class TamarackDbEventStoreAdapter implements AdapterInterface
{
    private const DEFAULT_LIMIT = 1000;

    private string $baseUri;

    private ?string $authToken;

    private EventNameResolverInterface $eventNameResolver;

    private SerializerInterface $eventSerializer;

    private SerializerInterface $metadataSerializer;

    public function __construct(
        string $baseUri,
        ?string $authToken,
        EventNameResolverInterface $eventNameResolver,
        SerializerInterface $eventSerializer,
        SerializerInterface $metadataSerializer,
    ) {
        $this->baseUri = rtrim($baseUri, '/');
        $this->authToken = $authToken;
        $this->eventNameResolver = $eventNameResolver;
        $this->eventSerializer = $eventSerializer;
        $this->metadataSerializer = $metadataSerializer;
    }

    public function fetch(Query $query, int $fromSequence = 0): StoredRecordedEventStream
    {
        $stream = new StoredRecordedEventStream();

        foreach ($this->readMatching($query, $fromSequence) as $row) {
            $stream = $stream
                ->withRecordedEvents($this->buildEventFromRow($row))
                ->withHighestSequence((int) $row['sequence']);
        }

        return $stream;
    }

    public function append(RecordedEventStream $stream, Query $concurrencyCheck, ?int $expectedSequence): void
    {
        if (!count($stream)) {
            return;
        }

        $events = [];
        /** @var RecordedEvent $recordedEvent */
        foreach ($stream as $recordedEvent) {
            $events[] = [
                'type' => $this->eventNameResolver->resolveName($recordedEvent->getEvent()::class),
                'identifiers' => $recordedEvent->getEvent()->getIdentifiers()->toArray(),
                'metadata' => $recordedEvent->getMetadata()->toArray(),
                'payload' => $this->eventSerializer->serialize($recordedEvent->getEvent()),
            ];
        }

        $body = ['events' => $events];
        if ($expectedSequence !== null) {
            $body['condition'] = [
                'failIfEventsMatch' => (new QueryToTamarackDbQuery($concurrencyCheck, $this->eventNameResolver))->toPayload(),
                'afterSequence' => $expectedSequence,
            ];
        }

        $this->httpAppend($body);
    }

    public function inspect(InspectorInterface $inspector): void
    {
        foreach ($this->readMatching($inspector->getQuery(), 0) as $row) {
            $inspector->inspect($this->buildEventFromRow($row));
        }
    }

    public function purge(): void
    {
        throw new TamarackDbEventStoreException(
            'TamarackDB is an append-only event store; purge() is not supported.',
        );
    }

    /** @return Generator<array> */
    private function readMatching(Query $query, int $fromSequence): Generator
    {
        $queryPayload = (new QueryToTamarackDbQuery($query, $this->eventNameResolver))->toPayload();
        $afterSequence = $fromSequence > 0 ? $fromSequence - 1 : null;

        do {
            $body = ['query' => $queryPayload, 'limit' => self::DEFAULT_LIMIT];
            if ($afterSequence !== null) {
                $body['afterSequence'] = $afterSequence;
            }

            $result = $this->httpRead($body);
            foreach ($result['events'] as $row) {
                yield $row;
                $afterSequence = (int) $row['sequence'];
            }
        } while ($result['hasMore']);
    }

    private function buildEventFromRow(array $row): RecordedEvent
    {
        return RecordedEvent::create(
            $this->eventSerializer->deserialize($row['payload'], $row['type']),
            $this->metadataSerializer->deserialize(json_encode($row['metadata'] ?? [])),
            new DateTimeImmutable($row['time']),
        );
    }

    /** @return array{hasMore: bool, events: array[]} */
    private function httpRead(array $queryBody): array
    {
        [$status, $body] = $this->execute('QUERY', '/read', $queryBody);

        if ($status !== 200) {
            throw $this->buildException($status, $body);
        }

        $lines = array_values(array_filter(explode("\n", trim($body)), fn ($line) => $line !== ''));
        $header = json_decode((string) array_shift($lines), true);

        return [
            'hasMore' => (bool) ($header['hasMore'] ?? false),
            'events' => array_map(fn ($line) => json_decode($line, true), $lines),
        ];
    }

    private function httpAppend(array $body): void
    {
        [$status, $responseBody] = $this->execute('POST', '/append', $body);

        if ($status === 409) {
            throw new ConcurrencyException();
        }
        if ($status !== 200) {
            throw $this->buildException($status, $responseBody);
        }
    }

    /** @return array{0: int, 1: string} */
    private function execute(string $method, string $path, array $body): array
    {
        $headers = ['Content-Type: application/json'];
        if ($this->authToken !== null) {
            $headers[] = sprintf('Authorization: Bearer %s', $this->authToken);
        }

        $handle = curl_init($this->baseUri . $path);
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($handle);
        if ($response === false) {
            $error = curl_error($handle);
            curl_close($handle);
            throw new TamarackDbEventStoreException(sprintf('TamarackDB request failed: %s', $error));
        }

        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return [$status, $response];
    }

    private function buildException(int $status, string $body): TamarackDbEventStoreException
    {
        $decoded = json_decode($body, true);
        $message = $decoded['message'] ?? $decoded['error'] ?? $body;
        return new TamarackDbEventStoreException(sprintf('TamarackDB request failed with status %d: %s', $status, $message));
    }
}
