<?php

namespace App\Domain\EventSourcing;

use App\Domain\EventSourcing\Contracts\AggregateRoot as Contract;
use App\Domain\EventSourcing\Contracts\Event;

trait AggregateRoot
{
    /**
     * @var Event[]
     */
    private $pendingEvents = [];

    /**
     * @var int
     */
    private $version = 0;

    /**
     * Reconstruct the state of the entity from a message stream.
     *
     * @param \App\Domain\EventSourcing\Contracts\Event[] $events
     */
    public static function reconstitute(array $events): Contract
    {
        /** @var Contract&static $instance */
        $instance = new static(); // @phpstan-ignore-line

        foreach ($events as $event) {
            $instance->apply($event);
        }

        return $instance;
    }

    /**
     * Accessor for the current version.
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Accessor for uncommitted events.
     *
     * @return \App\Domain\EventSourcing\Contracts\Event[]
     */
    public function getPendingEvents(): array
    {
        return $this->pendingEvents;
    }

    /**
     * Clear uncommitted events.
     */
    public function commit(): void
    {
        $this->pendingEvents = [];
    }

    /**
     * Record that an event occurred and put it on the pending list.
     */
    protected function record(Event $event): void
    {
        $this->apply($event);

        $this->pendingEvents[] = $event;
    }

    /**
     * Apply the effects of an event to the entity.
     */
    private function apply(Event $event): void
    {
        ++$this->version;

        $method = sprintf('apply%s', class_basename($event));

        if (!method_exists($this, $method)) {
            return;
        }

        $this->{$method}($event);
    }
}
