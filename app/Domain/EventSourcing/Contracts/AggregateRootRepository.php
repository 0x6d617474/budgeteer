<?php

namespace App\Domain\EventSourcing\Contracts;

use App\Domain\UUID;

interface AggregateRootRepository
{
    public function __construct(MessageRepository $repository, MessageDispatcher $dispatcher);

    /**
     * Check to see if an aggregate root exists.
     */
    public function exists(string $type, UUID $id): bool;

    /**
     * Load an aggregate root from the underlying message repository.
     */
    public function load(string $type, UUID $id): AggregateRoot;

    /**
     * Persist an aggregate root to the underlying message repository.
     */
    public function persist(AggregateRoot $instance, UUID $id): void;
}
