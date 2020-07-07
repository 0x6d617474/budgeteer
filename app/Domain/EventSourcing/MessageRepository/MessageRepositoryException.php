<?php

namespace App\Domain\EventSourcing\MessageRepository;

use App\Domain\DomainException;
use App\Domain\UUID;

final class MessageRepositoryException extends DomainException
{
    public static function streamNotFound(UUID $streamId): self
    {
        return new static(sprintf('Stream not found with ID: %s', $streamId->toString()));
    }

    public static function versionConflict(UUID $streamId, int $version): self
    {
        return new static(sprintf('A message with version %d was already committed for stream with ID: %s', $version, (string) $streamId));
    }
}
