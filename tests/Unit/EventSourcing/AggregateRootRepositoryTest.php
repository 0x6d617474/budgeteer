<?php

namespace Tests\Unit\EventSourcing;

use App\Domain\DomainException;
use App\Domain\EventSourcing\AggregateRootRepository;
use App\Domain\EventSourcing\Contracts\AggregateRoot;
use App\Domain\EventSourcing\Contracts\Event;
use App\Domain\EventSourcing\Contracts\Message;
use App\Domain\EventSourcing\Contracts\MessageRepository;
use App\Domain\UUID;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Domain\EventSourcing\AggregateRootRepository
 */
final class AggregateRootRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function exists_defers_to_message_repository(): void
    {
        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class, function ($mock) {
            $mock->shouldReceive('exists');
        });

        $repository = new AggregateRootRepository($messageRepository);
        $repository->exists('__', UUID::generate());
    }

    /**
     * @test
     */
    public function load_reconstitutes_aggregates_from_messages(): void
    {
        $event = $this->mock(Event::class);

        $message = $this->mock(Message::class, function ($mock) use ($event) {
            $mock->shouldReceive('getEvent')->andReturn($event);
        });

        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class, function ($mock) use ($message) {
            $mock->shouldReceive('exists')->andReturn(true);
            $mock->shouldReceive('load')->andReturn([$message]);
        });

        $aggregate = $this->mock(AggregateRoot::class, function ($mock) use ($event) {
            $mock->shouldReceive('reconstitute')->withArgs([[$event]]);
        });

        $repository = new AggregateRootRepository($messageRepository);
        $repository->load(\get_class($aggregate), UUID::generate());
    }

    /**
     * @test
     */
    public function loading_a_missing_aggregate_fails(): void
    {
        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class, function ($mock) {
            $mock->shouldReceive('exists')->andReturn(false);
        });

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('not found');

        $repository = new AggregateRootRepository($messageRepository);
        $repository->load('__', UUID::generate());
    }

    /**
     * @test
     */
    public function persisting_without_events_does_nothing(): void
    {
        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class, function ($mock) {
            $mock->shouldNotReceive('append');
        });

        /** @var AggregateRoot $aggregate */
        $aggregate = $this->mock(AggregateRoot::class, function ($mock) {
            $mock->shouldReceive('getPendingEvents')->andReturn([]);
        });

        $repository = new AggregateRootRepository($messageRepository);
        $repository->persist($aggregate, UUID::generate());
    }

    /**
     * @test
     */
    public function persisting_with_events_appends_messages_to_message_repository(): void
    {
        $events = [
            $this->mock(Event::class),
            $this->mock(Event::class),
            $this->mock(Event::class),
        ];

        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class, function ($mock) use ($events) {
            $mock->shouldReceive('append')->andReturnUsing(function (string $streamId, array $messages) use ($events) {
                /** @var Message[] $messages */
                $this->assertSame(\count($events), \count($messages));

                foreach ($messages as $i => $message) {
                    $this->assertSame($events[$i], $message->getEvent());
                }
            });
        });

        /** @var AggregateRoot $aggregate */
        $aggregate = $this->mock(AggregateRoot::class, function ($mock) use ($events) {
            $mock->shouldReceive('getPendingEvents')->andReturn($events);
            $mock->shouldReceive('getVersion')->andReturn(\count($events));
            $mock->shouldReceive('commit');
        });

        $repository = new AggregateRootRepository($messageRepository);
        $repository->persist($aggregate, UUID::generate());
    }

    /**
     * @test
     */
    public function persisting_with_events_uses_correct_version_numbers(): void
    {
        $aggregateVersion = 37; // This should be the version of the last message

        $events = [
            $this->mock(Event::class),
            $this->mock(Event::class),
            $this->mock(Event::class),
        ];

        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class, function ($mock) use ($aggregateVersion) {
            $mock->shouldReceive('append')->andReturnUsing(function (string $streamId, array $messages) use ($aggregateVersion) {
                /** @var Message $message */
                $message = end($messages);

                $this->assertSame($aggregateVersion, $message->getVersion());
            });
        });

        /** @var AggregateRoot $aggregate */
        $aggregate = $this->mock(AggregateRoot::class, function ($mock) use ($events, $aggregateVersion) {
            $mock->shouldReceive('getPendingEvents')->andReturn($events);
            $mock->shouldReceive('getVersion')->andReturn($aggregateVersion);
            $mock->shouldReceive('commit');
        });

        $repository = new AggregateRootRepository($messageRepository);
        $repository->persist($aggregate, UUID::generate());
    }

    /**
     * @test
     */
    public function persisting_with_exception_raises_exception(): void
    {
        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class, function ($mock) {
            $mock->shouldReceive('append')->andThrow(
                DomainException::persistanceError('__', UUID::generate(), new \Exception('TEST'))
            );
        });

        /** @var AggregateRoot $aggregate */
        $aggregate = $this->mock(AggregateRoot::class, function ($mock) {
            $mock->shouldReceive('getPendingEvents')->andReturn([$this->mock(Event::class)]);
            $mock->shouldReceive('getVersion')->andReturn(0);
        });

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Failed to persist');

        $repository = new AggregateRootRepository($messageRepository);
        $repository->persist($aggregate, UUID::generate());
    }
}
