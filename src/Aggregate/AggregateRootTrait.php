<?php

declare(strict_types=1);

namespace Backslash\Aggregate;

trait AggregateRootTrait
{
    private string $_aggregateId;

    private int $_version = 0;

    private Stream $_newEvents;

    public function __construct(string $aggregateId)
    {
        $this->_aggregateId = $aggregateId;
        $this->_newEvents = new Stream($aggregateId, self::getType());
    }

    public function getAggregateId(): string
    {
        return $this->_aggregateId;
    }

    public function getVersion(): int
    {
        return $this->_version;
    }

    public function peekNewEvents(): Stream
    {
        return $this->_newEvents;
    }

    public function pullNewEvents(): Stream
    {
        $stream = $this->peekNewEvents();
        $this->_newEvents = new Stream($this->getAggregateId(), self::getType());
        return $stream;
    }

    public function replayStream(Stream $stream): void
    {
        foreach ($stream->getRecordedEvents() as $recordedEvent) {
            $this->_version++;
            $this->_handle($recordedEvent);
        }
    }

    private function apply(EventInterface $event): void
    {
        $this->_version++;
        $recordedEvent = RecordedEvent::createNow($event, new Metadata(), $this->_version);
        $this->_handle($recordedEvent);
        $this->_newEvents = $this->_newEvents->withRecordedEvent($recordedEvent);
    }

    private function _handle(RecordedEvent $recordedEvent): void
    {
        $parts = explode('\\', $recordedEvent->getEvent()::class);
        $method = 'apply' . end($parts);
        if (method_exists($this, $method)) {
            $this->$method($recordedEvent->getEvent(), $recordedEvent);
        }
    }
}
