<?php

namespace App\Domain\EventSourcing;

use App\Domain\DomainException;
use App\Domain\EventSourcing\Contracts\AggregateRoot;
use App\Domain\EventSourcing\Contracts\AggregateRootRepository as Contract;
use App\Domain\EventSourcing\Contracts\Event;
use App\Domain\EventSourcing\Contracts\Message as MessageContract;
use App\Domain\EventSourcing\Contracts\MessageDispatcher;
use App\Domain\EventSourcing\Contracts\MessageRepository;
use App\Domain\EventSourcing\MessageRepository\Message;
use App\Domain\UUID;

final class AggregateRootRepository implements Contract
{
    /**
     * Message repository.
     *
     * @var \App\Domain\EventSourcing\Contracts\MessageRepository
     */
    private $repository;

    /**
     * @var \App\Domain\EventSourcing\Contracts\MessageDispatcher
     */
    private $dispatcher;

    public function __construct(MessageRepository $repository, MessageDispatcher $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $type, UUID $id): bool
    {
        return $this->repository->exists($this->constructStreamId($type, $id));
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $type, UUID $id): AggregateRoot
    {
        if (!$this->exists($type, $id)) {
            throw DomainException::aggregateRootNotFound($type, $id);
        }

        $messages = $this->repository->load($this->constructStreamId($type, $id));
        $events = array_map(function (MessageContract $message) {
            return $message->getEvent();
        }, $messages);

        /** @var AggregateRoot $type */
        return $type::reconstitute($events);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(AggregateRoot $instance, UUID $id): void
    {
        $events = $instance->getPendingEvents();

        if (0 === \count($events)) {
            return;
        }

        $type = \get_class($instance);
        $streamId = $this->constructStreamId($type, $id);

        $version = $instance->getVersion() - \count($events); // starting version number

        $messages = array_map(function (Event $event) use ($streamId, &$version) {
            return new Message($streamId, ++$version, $event, time());
        }, $events);

        try {
            $this->repository->append($streamId, $messages);
        } catch (\Exception $exception) {
            throw DomainException::persistanceError($type, $id, $exception);
        }

        $instance->commit();

        $this->dispatcher->publish($messages);
    }

    /**
     * Construct a stream id from the aggregate root type and id.
     */
    private function constructStreamId(string $type, UUID $id): UUID
    {
        return UUID::generate(sprintf('%s:%s', $type, (string) $id));
    }
}
