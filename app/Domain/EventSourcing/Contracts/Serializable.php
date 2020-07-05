<?php

namespace App\Domain\EventSourcing\Contracts;

interface Serializable
{
    /**
     * Serialize to an array.
     */
    public function serialize(): array;

    /**
     * Deserialize an array back into an object.
     */
    public static function deserialize(array $data): self;
}
