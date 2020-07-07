<?php

namespace App\Domain;

use Ramsey\Uuid\Uuid as Concrete;
use Ramsey\Uuid\UuidInterface;

final class UUID
{
    /**
     * @var \Ramsey\Uuid\UuidInterface
     */
    private $concrete;

    private function __construct(?UuidInterface $concrete = null)
    {
        if (null === $concrete) {
            $concrete = Concrete::uuid4();
        }

        $this->concrete = $concrete;
    }

    public function __toString(): string
    {
        return $this->concrete->toString();
    }

    /**
     * Generate a new UUID from a seed.
     */
    public static function generate(?string $seed = null): self
    {
        if (null !== $seed) {
            return new static(Concrete::uuid5(Concrete::NIL, $seed));
        }

        return new static();
    }

    /**
     * Create a UUID object from its string representation.
     */
    public static function fromString(string $string): self
    {
        return new static(Concrete::fromString($string));
    }

    /**
     * Convert to string representation.
     */
    public function toString(): string
    {
        return (string) $this;
    }
}
