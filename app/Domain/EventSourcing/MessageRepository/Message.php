<?php

namespace App\Domain\EventSourcing\MessageRepository;

use App\Domain\EventSourcing\Contracts\Event;
use App\Domain\EventSourcing\Contracts\Message as Contract;
use App\Domain\UUID;

final class Message implements Contract
{
    /**
     * Stream unique id.
     *
     * @var UUID
     */
    private $streamId;

    /**
     * Stream version.
     *
     * @var int
     */
    private $version;

    /**
     * Event in the message envelope.
     *
     * @var Event
     */
    private $event;

    /**
     * Message timestamp.
     *
     * @var int
     */
    private $timestamp;

    public function __construct(UUID $streamId, int $version, Event $event, int $timestamp)
    {
        $this->streamId = $streamId;
        $this->version = $version;
        $this->event = $event;
        $this->timestamp = $timestamp;
    }

    /**
     * Accessor for the stream unique id.
     */
    public function getStreamId(): UUID
    {
        return $this->streamId;
    }

    /**
     * Accessor for the stream version.
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Accessor for the event.
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * Accessor for the timestamp.
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
}
