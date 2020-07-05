<?php

namespace App\Domain\EventSourcing\MessageRepository;

use App\Domain\EventSourcing\Contracts\MessageRepository as Contract;
use App\Domain\UUID;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;

final class DatabaseMessageRepository implements Contract
{
    /**
     * Database connection to use.
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * Name of the table that holds the event store data.
     *
     * @var string
     */
    private $table;

    public function __construct(ConnectionInterface $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(UUID $streamId): bool
    {
        return $this->connection->table($this->table)
            ->where('stream_id', (string) $streamId)
            ->count('stream_id') > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function load(UUID $streamId): array
    {
        $rows = $this->connection->table($this->table)
            ->where('stream_id', (string) $streamId)
            ->orderBy('version', 'ASC');

        if (0 === $rows->count('stream_id')) {
            throw MessageRepositoryException::streamNotFound($streamId);
        }

        return $rows->get()->map(function ($row) {
            return $this->deserializeMessage((array) $row);
        })->all();
    }

    /**
     * {@inheritdoc}
     */
    public function append(UUID $streamId, array $messages): void
    {
        if (0 === \count($messages)) {
            return;
        }

        $this->connection->transaction(function () use ($streamId, $messages) {
            foreach ($messages as $message) {
                try {
                    $this->connection->table($this->table)->insert([
                        'stream_id' => (string) $streamId,
                        'version'   => $message->getVersion(),
                        'payload'   => json_encode($message->getEvent()->serialize()),
                        'timestamp' => $message->getTimestamp(),
                        'type'      => \get_class($message->getEvent()),
                    ]);
                } catch (QueryException $exception) {
                    if (str_contains($exception->getMessage(), 'Integrity constraint violation')) {
                        throw MessageRepositoryException::versionConflict($streamId, $message->getVersion());
                    }

                    throw $exception;
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function version(UUID $streamId): int
    {
        $lastEvent = $this->connection->table($this->table)
            ->where('stream_id', (string) $streamId)
            ->orderBy('version', 'desc')
            ->first(['version']);

        if (null === $lastEvent) {
            throw MessageRepositoryException::streamNotFound($streamId);
        }

        return $lastEvent->version;
    }

    /**
     * Deserialize a message entry into a message object.
     */
    private function deserializeMessage(array $encoded): Message
    {
        return new Message(
            UUID::fromString($encoded['stream_id']),
            $encoded['version'],
            $encoded['type']::deserialize(json_decode($encoded['payload'], true)),
            $encoded['timestamp']
        );
    }
}
