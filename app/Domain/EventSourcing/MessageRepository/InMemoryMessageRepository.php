<?php

namespace App\Domain\EventSourcing\MessageRepository;

use App\Domain\EventSourcing\Contracts\MessageRepository as Contract;
use App\Domain\UUID;

final class InMemoryMessageRepository implements Contract
{
    /**
     * @var array
     */
    private $messages = [];

    /**
     * {@inheritdoc}
     */
    public function exists(UUID $streamId): bool
    {
        return \array_key_exists((string) $streamId, $this->messages);
    }

    /**
     * {@inheritdoc}
     */
    public function load(UUID $streamId): array
    {
        if (!$this->exists($streamId)) {
            throw MessageRepositoryException::streamNotFound($streamId);
        }

        // The array keys are an implementation detail and should be stripped
        return array_values($this->messages[(string) $streamId]);
    }

    /**
     * {@inheritdoc}
     */
    public function append(UUID $streamId, array $messages): void
    {
        $key = (string) $streamId;

        if (!$this->exists($streamId)) {
            $this->messages[$key] = [];
        }

        foreach ($messages as $message) {
            $version = $message->getVersion();

            /*
             * We optimistically lock event streams.
             *
             * This means that if two people load the same event stream
             * and attempt to append messages that diverge from a common
             * ancestor, we only accept one.
             *
             * @see https://en.wikipedia.org/wiki/Optimistic_concurrency_control
             */
            if (\array_key_exists($version, $this->messages[$key])) {
                throw MessageRepositoryException::versionConflict($streamId, $version);
            }

            $this->messages[$key][$version] = $message;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function version(UUID $streamId): int
    {
        if (!$this->exists($streamId)) {
            throw MessageRepositoryException::streamNotFound($streamId);
        }

        /** @var \App\Domain\EventSourcing\Contracts\Message[] $stream */
        $stream = $this->messages[(string) $streamId];
        end($stream);

        return (int) key($stream);
    }
}
