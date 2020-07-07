<?php

namespace App\Domain\EventSourcing\Contracts;

interface MessageConsumer
{
    /**
     * Respond to a message in some way.
     */
    public function handle(Message $message): void;
}
