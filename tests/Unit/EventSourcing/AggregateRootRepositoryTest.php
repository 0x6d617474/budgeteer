<?php

namespace Tests\Unit\EventSourcing;

use App\Domain\DomainException;
use App\Domain\EventSourcing\AggregateRootRepository;
use App\Domain\EventSourcing\Contracts\AggregateRoot;
use App\Domain\EventSourcing\Contracts\Event;
use App\Domain\EventSourcing\Contracts\Message;
use App\Domain\EventSourcing\Contracts\MessageDispatcher;
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
            $mock->expects('exists');
        });

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->mock(MessageDispatcher::class);

        $repository = new AggregateRootRepository($messageRepository, $dispatcher);
        $repository->exists('__', UUID::generate());
    }

    /**
     * @test
     */
    public function load_reconstitutes_aggregates_from_messages(): void
    {
        $event = $this->mock(Event::class);

        $message = $this->mock(Message::class, function ($mock) use ($event) {
            $mock->expects('getEvent')->andReturn($event);
        });

        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class, function ($mock) use ($message) {
            $mock->expects('exists')->andReturn(true);
            $mock->expects('load')->andReturn([$message]);
        });

        $aggregate = $this->mock(AggregateRoot::class, function ($mock) use ($event) {
            $mock->expects('reconstitute')->withArgs([[$event]]);
        });

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->mock(MessageDispatcher::class);

        $repository = new AggregateRootRepository($messageRepository, $dispatcher);
        $repository->load(\get_class($aggregate), UUID::generate());
    }

    /**
     * @test
     */
    public function loading_a_missing_aggregate_fails(): void
    {
        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class, function ($mock) {
            $mock->expects('exists')->andReturn(false);
        });

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->mock(MessageDispatcher::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('not found');

        $repository = new AggregateRootRepository($messageRepository, $dispatcher);
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
            $mock->expects('getPendingEvents')->andReturn([]);
        });

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->mock(MessageDispatcher::class);

        $repository = new AggregateRootRepository($messageRepository, $dispatcher);
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
            $mock->expects('append')->andReturnUsing(function (string $streamId, array $messages) use ($events) {
                /** @var Message[] $messages */
                $this->assertSame(\count($events), \count($messages));

                foreach ($messages as $i => $message) {
                    $this->assertSame($events[$i], $message->getEvent());
                }
            });
        });

        /** @var AggregateRoot $aggregate */
        $aggregate = $this->mock(AggregateRoot::class, function ($mock) use ($events) {
            $mock->expects('getPendingEvents')->andReturn($events);
            $mock->expects('getVersion')->andReturn(\count($events));
            $mock->expects('commit');
        });

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->mock(MessageDispatcher::class, function ($mock) {
            $mock->expects('publish');
        });

        $repository = new AggregateRootRepository($messageRepository, $dispatcher);
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
            $mock->expects('append')->andReturnUsing(function (string $streamId, array $messages) use ($aggregateVersion) {
                /** @var Message $message */
                $message = end($messages);

                $this->assertSame($aggregateVersion, $message->getVersion());
            });
        });

        /** @var AggregateRoot $aggregate */
        $aggregate = $this->mock(AggregateRoot::class, function ($mock) use ($events, $aggregateVersion) {
            $mock->expects('getPendingEvents')->andReturn($events);
            $mock->expects('getVersion')->andReturn($aggregateVersion);
            $mock->expects('commit');
        });

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->mock(MessageDispatcher::class, function ($mock) {
            $mock->expects('publish');
        });

        $repository = new AggregateRootRepository($messageRepository, $dispatcher);
        $repository->persist($aggregate, UUID::generate());
    }

    /**
     * @test
     */
    public function persisting_with_exception_raises_exception(): void
    {
        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class, function ($mock) {
            $mock->expects('append')->andThrow(
                DomainException::persistanceError('__', UUID::generate(), new \Exception('TEST'))
            );
        });

        /** @var AggregateRoot $aggregate */
        $aggregate = $this->mock(AggregateRoot::class, function ($mock) {
            $mock->expects('getPendingEvents')->andReturn([$this->mock(Event::class)]);
            $mock->expects('getVersion')->andReturn(0);
        });

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->mock(MessageDispatcher::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Failed to persist');

        $repository = new AggregateRootRepository($messageRepository, $dispatcher);
        $repository->persist($aggregate, UUID::generate());
    }
}
