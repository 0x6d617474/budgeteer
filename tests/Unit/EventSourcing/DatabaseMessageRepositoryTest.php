<?php

namespace Tests\Unit\EventSourcing;

use App\Domain\EventSourcing\Contracts\Event;
use App\Domain\EventSourcing\Contracts\Message;
use App\Domain\EventSourcing\MessageRepository\DatabaseMessageRepository;
use App\Domain\EventSourcing\MessageRepository\MessageRepositoryException;
use App\Domain\UUID;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Domain\EventSourcing\MessageRepository\DatabaseMessageRepository
 */
final class DatabaseMessageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var string
     */
    private $table = 'eventstore';

    /**
     * @test
     */
    public function validate_input_output_and_stream_version(): void
    {
        $messages = $this->createMessages($id = UUID::generate(), $count = 3);

        $repository = new DatabaseMessageRepository(DB::connection(), $this->table);
        $repository->append($id, $messages);

        $loaded = $repository->load($id);
        foreach ($loaded as $i => $message) {
            $this->assertSame($messages[$i]->getStreamId()->toString(), $message->getStreamId()->toString());
            $this->assertSame($messages[$i]->getVersion(), $message->getVersion());
        }

        $this->assertSame($count, $repository->version($id));
    }

    /**
     * @test
     */
    public function check_locking_works_as_intended(): void
    {
        $messages = $this->createMessages($id = UUID::generate(), $count = 3);

        $repository = new DatabaseMessageRepository(DB::connection(), $this->table);
        $repository->append($id, $messages);

        $this->expectException(MessageRepositoryException::class);
        $this->expectExceptionMessage('already committed');
        $repository->append($id, $messages);
    }

    /**
     * @test
     */
    public function check_underlying_exceptions_are_not_squashed_when_appending(): void
    {
        $messages = $this->createMessages($id = UUID::generate(), $count = 3);

        /** @var ConnectionInterface $connection */
        $connection = $this->mock(ConnectionInterface::class, function ($mock) {
            $mock->expects('table')->andThrow(new QueryException('TESTING', [], new \Exception('_')));
            $mock->expects('transaction')->andReturnUsing(function (callable $closure) {
                return $closure();
            });
        });

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('TESTING');

        $repository = new DatabaseMessageRepository($connection, $this->table);
        $repository->append($id, $messages);
    }

    /**
     * @test
     */
    public function appending_with_no_messages_should_do_nothing(): void
    {
        /** @var ConnectionInterface $connection */
        $connection = $this->mock(ConnectionInterface::class, function ($mock) {
            $mock->shouldNotReceive('transaction');
        });

        $repository = new DatabaseMessageRepository($connection, $this->table);
        $repository->append(UUID::generate(), []);
    }

    /**
     * @test
     */
    public function attempting_to_load_a_missing_stream_fails(): void
    {
        $repository = new DatabaseMessageRepository(DB::connection(), $this->table);

        $this->expectException(MessageRepositoryException::class);
        $this->expectExceptionMessage('Stream not found');

        $repository->load(UUID::generate());
    }

    /**
     * @test
     */
    public function attempting_to_get_the_version_of_a_missing_stream_fails(): void
    {
        $repository = new DatabaseMessageRepository(DB::connection(), $this->table);

        $this->expectException(MessageRepositoryException::class);
        $this->expectExceptionMessage('Stream not found');

        $repository->version(UUID::generate());
    }

    /**
     * @test
     */
    public function exists_returns_false_for_missing_stream(): void
    {
        $repository = new DatabaseMessageRepository(DB::connection(), $this->table);

        $this->assertFalse($repository->exists(UUID::generate()));
    }

    /**
     * @test
     */
    public function exists_returns_true_for_stream_that_exists(): void
    {
        $messages = $this->createMessages($id = UUID::generate(), $count = 3);

        $repository = new DatabaseMessageRepository(DB::connection(), $this->table);
        $repository->append($id, $messages);

        $this->assertTrue($repository->exists($id));
    }

    /**
     * Create a list of messages for use in the test.
     *
     * @return \App\Domain\EventSourcing\Contracts\Message[]
     */
    private function createMessages(UUID $streamId, int $count = 3): array
    {
        $messages = [];

        for ($i = 1; $i <= $count; ++$i) {
            $messages[] = $this->mock(Message::class, function ($mock) use ($i, $streamId) {
                $mock->allows('getStreamId')->andReturn($streamId);
                $mock->allows('getVersion')->andReturn($i);
                $mock->allows('getTimestamp')->andReturn(time());
                $mock->allows('getEvent')->andReturn($this->mock(Event::class, function ($mock) {
                    $mock->allows('serialize')->andReturn([]);
                    $mock->allows('deserialize')->andReturn($mock);
                }));
            });
        }

        return $messages;
    }
}
