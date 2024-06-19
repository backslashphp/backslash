<?php

declare(strict_types=1);

namespace Backslash\Domain;

use Backslash\Clock\Clock;

trait StateTrait
{
    protected RecordedEventStream $_newEvents;

    public function __construct()
    {
        $this->_newEvents = new RecordedEventStream();
    }

    public function peekNewEvents(): RecordedEventStream
    {
        return $this->_newEvents;
    }

    public function pullNewEvents(): RecordedEventStream
    {
        $stream = $this->peekNewEvents();
        $this->_newEvents = new RecordedEventStream();
        return $stream;
    }

    public function replayEvents(RecordedEventStream $stream): void
    {
        foreach ($stream as $event) {
            $this->_handle($event);
        }
    }

    protected function apply(EventInterface $event): void
    {
        $recordedEvent = RecordedEvent::create($event, new Metadata(), Clock::now());
        $this->_handle($recordedEvent);
        $this->_newEvents = $this->_newEvents->withRecordedEvents($recordedEvent);
    }

    protected function _handle(RecordedEvent $recordedEvent): void
    {
        $parts = explode('\\', $recordedEvent->getEvent()::class);
        $method = 'apply' . end($parts);
        if (method_exists($this, $method)) {
            $this->$method($recordedEvent->getEvent(), $recordedEvent);
        }
    }
}
