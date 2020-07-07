<?php

namespace App\Domain\EventSourcing\Contracts;

interface MessageDispatcher
{
    public function __construct(array $subscribers = []);

    /**
     * Add a consumer to the list of subscriptions.
     */
    public function subscribe(MessageConsumer $subscriber): void;

    /**
     * Publish a message to all subscribed consumers.
     *
     * @param Message[] $messages
     */
    public function publish(array $messages): void;
}
