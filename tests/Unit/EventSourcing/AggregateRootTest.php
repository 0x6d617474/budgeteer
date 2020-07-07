<?php

namespace Tests\Unit\EventSourcing;

use App\Domain\EventSourcing\AggregateRoot;
use App\Domain\EventSourcing\Contracts\AggregateRoot as Contract;
use App\Domain\EventSourcing\Contracts\Event;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Domain\EventSourcing\AggregateRoot
 */
final class AggregateRootTest extends TestCase
{
    /**
     * @test
     */
    public function reconstituting_without_events_has_version_0(): void
    {
        /** @var \App\Domain\EventSourcing\Contracts\AggregateRoot $mock */
        $mock = $this->getMockForAbstractClass(AggregateRootMock::class);

        $aggregate = $mock::reconstitute([]);
        $this->assertSame(0, $aggregate->getVersion());
    }

    /**
     * @test
     */
    public function reconstituting_events_applies_the_correct_version(): void
    {
        $targetVersion = 12;

        $events = [];
        for ($i = 0; $i < $targetVersion; ++$i) {
            $events[] = $this->mock(Event::class);
        }

        /** @var \App\Domain\EventSourcing\Contracts\AggregateRoot $mock */
        $mock = $this->getMockForAbstractClass(AggregateRootMock::class);

        $aggregate = $mock::reconstitute($events);
        $this->assertSame($targetVersion, $aggregate->getVersion());
    }

    /**
     * @test
     */
    public function reconstituted_events_are_not_pending(): void
    {
        $events = [$this->mock(Event::class)];

        /** @var \App\Domain\EventSourcing\Contracts\AggregateRoot $mock */
        $mock = $this->getMockForAbstractClass(AggregateRootMock::class);

        $aggregate = $mock::reconstitute($events);
        $this->assertSame([], $aggregate->getPendingEvents());
    }

    /**
     * @test
     */
    public function commit_clears_pending_events(): void
    {
        /** @var \App\Domain\EventSourcing\Contracts\AggregateRoot $mock */
        $mock = $this->getMockForAbstractClass(AggregateRootMock::class);

        /** @var Event $event */
        $event = $this->mock(Event::class);

        /** @var \App\Domain\EventSourcing\Contracts\AggregateRoot $aggregate */
        $aggregate = $mock::create();   // @phpstan-ignore-line
        $aggregate->addEvent($event);   // @phpstan-ignore-line
        $aggregate->commit();

        $this->assertSame(0, \count($aggregate->getPendingEvents()));
    }

    /**
     * @test
     */
    public function commit_doesnt_affect_version(): void
    {
        /** @var \App\Domain\EventSourcing\Contracts\AggregateRoot $mock */
        $mock = $this->getMockForAbstractClass(AggregateRootMock::class);

        /** @var Event $event */
        $event = $this->mock(Event::class);

        /** @var \App\Domain\EventSourcing\Contracts\AggregateRoot $aggregate */
        $aggregate = $mock::create();   // @phpstan-ignore-line
        $this->assertSame(0, $aggregate->getVersion());

        $aggregate->addEvent($event);   // @phpstan-ignore-line
        $this->assertSame(1, $aggregate->getVersion());

        $aggregate->commit();
        $this->assertSame(1, $aggregate->getVersion());
    }
}

abstract class AggregateRootMock implements Contract
{
    use AggregateRoot;

    /**
     * Simple interface to allow testing changes to the pending events.
     */
    public function addEvent(Event $event): void
    {
        $this->record($event);
    }

    public static function create(): Contract
    {
        return new static();  // @phpstan-ignore-line
    }
}
