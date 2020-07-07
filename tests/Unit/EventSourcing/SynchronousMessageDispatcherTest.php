<?php

namespace Tests\Unit\EventSourcing;

use App\Domain\EventSourcing\Contracts\Message;
use App\Domain\EventSourcing\Contracts\MessageConsumer;
use App\Domain\EventSourcing\SynchronousMessageDispatcher;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Domain\EventSourcing\SynchronousMessageDispatcher
 */
final class SynchronousMessageDispatcherTest extends TestCase
{
    /**
     * @test
     */
    public function subscribers_must_be_the_correct_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SynchronousMessageDispatcher(['Foo']); // @phpstan-ignore-line
    }

    /**
     * @test
     */
    public function subscribers_receive_messages(): void
    {
        $messages = [$this->mock(Message::class)];

        /** @var MessageConsumer $subscriber */
        $subscriber = $this->mock(MessageConsumer::class, function ($mock) use ($messages) {
            $mock->expects('handle')->withArgs($messages);
        });

        $dispatcher = new SynchronousMessageDispatcher([$subscriber]);
        $dispatcher->publish($messages);
    }

    /**
     * @test
     */
    public function subscribers_added_later_receive_messages(): void
    {
        $messages = [$this->mock(Message::class)];

        /** @var MessageConsumer $subscriber */
        $subscriber = $this->mock(MessageConsumer::class, function ($mock) use ($messages) {
            $mock->expects('handle')->withArgs($messages);
        });

        $dispatcher = new SynchronousMessageDispatcher();
        $dispatcher->subscribe($subscriber);
        $dispatcher->publish($messages);
    }

    /**
     * @test
     */
    public function subscribers_receive_messages_in_order(): void
    {
        $messages = [];
        for ($i = 0; $i < 3; ++$i) {
            $messages[] = $this->mock(Message::class, function ($mock) use ($i) {
                $mock->expects('getVersion')->andReturn($i);
            });
        }

        $output = '';

        /** @var MessageConsumer $subscriber */
        $subscriber = $this->mock(MessageConsumer::class, function ($mock) use (&$output) {
            $mock->shouldReceive('handle')->atLeast()->once()->andReturnUsing(function ($message) use (&$output) {
                $output .= $message->getVersion();
            });
        });

        $dispatcher = new SynchronousMessageDispatcher([$subscriber]);
        $dispatcher->publish($messages);

        $this->assertSame('012', $output);
    }

    /**
     * @test
     */
    public function subscribers_receive_each_message_in_order_they_were_subscribed(): void
    {
        $messages = [$this->mock(Message::class)];

        $output = '';

        /** @var MessageConsumer[] $subscribers */
        $subscribers = [];

        for ($i = 0; $i < 3; ++$i) {
            $subscribers[] = $this->mock(MessageConsumer::class, function ($mock) use ($i, &$output) {
                $mock->expects('handle')->andReturnUsing(function () use ($i, &$output) {
                    $output .= $i;
                });
            });
        }

        $dispatcher = new SynchronousMessageDispatcher($subscribers);
        $dispatcher->publish($messages);

        $this->assertSame('012', $output);
    }

    /**
     * @test
     */
    public function subscribers_throwing_exceptions_doesnt_halt_queue(): void
    {
        $messages = [$this->mock(Message::class)];

        $output = '';

        /** @var MessageConsumer $badguy */
        $badguy = $this->mock(MessageConsumer::class, function ($mock) {
            $mock->expects('handle')->andThrow(new \Exception('TESTING'));
        });

        /** @var MessageConsumer $goodguy */
        $goodguy = $this->mock(MessageConsumer::class, function ($mock) use (&$output) {
            $mock->expects('handle')->andReturnUsing(function () use (&$output) {
                $output = 'handled';
            });
        });

        $dispatcher = new SynchronousMessageDispatcher([$badguy, $goodguy]);
        $dispatcher->publish($messages);

        $this->assertSame('handled', $output);
    }
}
