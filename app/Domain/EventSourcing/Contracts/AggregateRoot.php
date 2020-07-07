<?php

namespace App\Domain\EventSourcing\Contracts;

interface AggregateRoot
{
    /**
     * Reconstruct the state of the entity from a message stream.
     *
     * @param \App\Domain\EventSourcing\Contracts\Event[] $events
     */
    public static function reconstitute(array $events): self;

    /**
     * Accessor for the current version.
     */
    public function getVersion(): int;

    /**
     * Accessor for uncommitted events.
     *
     * @return \App\Domain\EventSourcing\Contracts\Event[]
     */
    public function getPendingEvents(): array;

    /**
     * Clear uncommitted events.
     */
    public function commit(): void;
}
