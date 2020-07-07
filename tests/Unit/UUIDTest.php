<?php

namespace Tests\Unit;

use App\Domain\UUID;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Domain\UUID
 */
final class UUIDTest extends TestCase
{
    /**
     * Ensure that the string value of the UUID is valid per the RFC.
     *
     * @test
     */
    public function valid_uuid(): void
    {
        $uuid = UUID::generate();

        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $this->assertNotFalse(preg_match_all($pattern, (string) $uuid));
    }

    /**
     * Ensure that two generated UUIDs are not equal.
     *
     * @test
     */
    public function randomly_generated_uuids_are_not_equal(): void
    {
        $this->assertNotSame(UUID::generate(), UUID::generate());
    }

    /**
     * Ensure that two UUIDs with the same seed are equal.
     *
     * @test
     */
    public function seed_generated_uuids_are_equal(): void
    {
        $this->assertSame(UUID::generate('seed')->toString(), UUID::generate('seed')->toString());
    }

    /**
     * Ensure that two UUIDs with different seeds are not equal.
     *
     * @test
     */
    public function different_seed_generated_uuids_are_not_equal(): void
    {
        $this->assertNotSame(UUID::generate('seed')->toString(), UUID::generate('new')->toString());
    }

    /**
     * Ensure that a converted UUID retains its value.
     *
     * @test
     */
    public function to_string_and_back(): void
    {
        $uuid = UUID::generate();
        $string = $uuid->toString();

        $this->assertSame($uuid->toString(), UUID::fromString($string)->toString());
    }
}
