<?php

namespace App\Domain\EventSourcing;

use App\Domain\EventSourcing\Contracts\MessageConsumer;
use App\Domain\EventSourcing\Contracts\MessageDispatcher as Contract;

final class SynchronousMessageDispatcher implements Contract
{
    /**
     * @var MessageConsumer[]
     */
    private $subscribers = [];

    /**
     * @param MessageConsumer[] $subscribers
     */
    public function __construct(array $subscribers = [])
    {
        array_walk($subscribers, function ($subscriber) {
            if (!$subscriber instanceof MessageConsumer) {
                throw new \InvalidArgumentException('Subscribers must implement MessageConsumer');
            }
        });

        $this->subscribers = $subscribers;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(MessageConsumer $subscriber): void
    {
        $this->subscribers[] = $subscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(array $messages): void
    {
        foreach ($messages as $message) {
            foreach ($this->subscribers as $subscriber) {
                try {
                    $subscriber->handle($message);
                } catch (\Throwable $exception) {
                    // Ignore - do not interrupt the queue
                }
            }
        }
    }
}
