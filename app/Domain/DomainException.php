<?php

namespace App\Domain;

class DomainException extends \RuntimeException
{
    public static function aggregateRootNotFound(string $type, UUID $id): self
    {
        return new self(sprintf('Aggregate root of type %s not found with ID: %s', $type, $id));
    }

    public static function persistanceError(string $type, UUID $id, \Throwable $e): self
    {
        return new self(sprintf('Failed to persist aggregate instance of type %s with ID: %s', $type, $id), 0, $e);
    }
}
