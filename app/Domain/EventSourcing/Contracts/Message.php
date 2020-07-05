<?php

namespace App\Domain\EventSourcing\Contracts;

use App\Domain\UUID;

interface Message
{
    public function __construct(UUID $streamId, int $version, Event $event, int $timestamp);

    public function getStreamId(): UUID;

    public function getVersion(): int;

    public function getEvent(): Event;

    public function getTimestamp(): int;
}
