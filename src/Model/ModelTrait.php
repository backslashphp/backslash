<?php

declare(strict_types=1);

namespace Backslash\Model;

use Backslash\Clock\Clock;
use Backslash\Event\EventInterface;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;

trait ModelTrait
{
    protected RecordedEventStream $_changes;

    public function __construct()
    {
        $this->_changes = new RecordedEventStream();
    }

    public function getChanges(): RecordedEventStream
    {
        return $this->_changes;
    }

    public function clearChanges(): void
    {
        $this->_changes = new RecordedEventStream();
    }

    public function applyEvents(RecordedEventStream $stream): void
    {
        foreach ($stream as $event) {
            $this->_apply($event);
        }
    }

    protected function record(EventInterface $event): void
    {
        $recordedEvent = RecordedEvent::create($event, new Metadata(), Clock::now());
        $this->_apply($recordedEvent);
        $this->_changes = $this->_changes->withRecordedEvents($recordedEvent);
    }

    protected function _apply(RecordedEvent $recordedEvent): void
    {
        $parts = explode('\\', $recordedEvent->getEvent()::class);
        $method = 'apply' . end($parts);
        if (method_exists($this, $method)) {
            $this->$method($recordedEvent->getEvent(), $recordedEvent);
        }
    }
}
