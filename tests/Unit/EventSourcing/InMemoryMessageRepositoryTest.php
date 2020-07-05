<?php

namespace Tests\Unit\EventSourcing;

use App\Domain\EventSourcing\Contracts\Message;
use App\Domain\EventSourcing\MessageRepository\InMemoryMessageRepository;
use App\Domain\EventSourcing\MessageRepository\MessageRepositoryException;
use App\Domain\UUID;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Domain\EventSourcing\MessageRepository\InMemoryMessageRepository
 */
final class InMemoryMessageRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function validate_input_output_and_stream_version(): void
    {
        $id = UUID::generate();
        $messages = $this->createMessages($count = 3);

        $repository = new InMemoryMessageRepository();
        $repository->append($id, $messages);

        $this->assertSame($messages, $repository->load($id));
        $this->assertSame($count, $repository->version($id));
    }

    /**
     * @test
     */
    public function check_locking_works_as_intended(): void
    {
        $id = UUID::generate();
        $messages = $this->createMessages($count = 3);

        $repository = new InMemoryMessageRepository();
        $repository->append($id, $messages);

        $this->expectException(MessageRepositoryException::class);
        $this->expectExceptionMessage('already committed');
        $repository->append($id, $messages);
    }

    /**
     * @test
     */
    public function attempting_to_load_a_missing_stream_fails(): void
    {
        $repository = new InMemoryMessageRepository();

        $this->expectException(MessageRepositoryException::class);
        $this->expectExceptionMessage('Stream not found');

        $repository->load(UUID::generate());
    }

    /**
     * @test
     */
    public function attempting_to_get_the_version_of_a_missing_stream_fails(): void
    {
        $repository = new InMemoryMessageRepository();

        $this->expectException(MessageRepositoryException::class);
        $this->expectExceptionMessage('Stream not found');

        $repository->version(UUID::generate());
    }

    /**
     * @test
     */
    public function exists_returns_false_for_missing_stream(): void
    {
        $repository = new InMemoryMessageRepository();

        $this->assertFalse($repository->exists(UUID::generate()));
    }

    /**
     * @test
     */
    public function exists_returns_true_for_stream_that_exists(): void
    {
        $id = UUID::generate();
        $messages = $this->createMessages($count = 3);

        $repository = new InMemoryMessageRepository();
        $repository->append($id, $messages);

        $this->assertTrue($repository->exists($id));
    }

    /**
     * Create a list of messages for use in the test.
     *
     * @return Message[]
     */
    private function createMessages(int $count = 3): array
    {
        $messages = [];

        for ($i = 1; $i <= $count; ++$i) {
            $messages[] = $this->mock(Message::class, function ($mock) use ($i) {
                $mock->shouldReceive('getVersion')->andReturn($i);
            });
        }

        return $messages;
    }
}
