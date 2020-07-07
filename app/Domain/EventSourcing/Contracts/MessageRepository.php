<?php

namespace App\Domain\EventSourcing\Contracts;

use App\Domain\UUID;

interface MessageRepository
{
    /**
     * Check to see if a stream exists in the repository.
     */
    public function exists(UUID $streamId): bool;

    /**
     * Load a stream of messages from the repository.
     *
     * @return \App\Domain\EventSourcing\Contracts\Message[]
     */
    public function load(UUID $streamId): array;

    /**
     * Append a stream of messages to the repository.
     *
     * @param \App\Domain\EventSourcing\Contracts\Message[] $messages
     */
    public function append(UUID $streamId, array $messages): void;

    /**
     * Get the current version of a stream.
     */
    public function version(UUID $streamId): int;
}
