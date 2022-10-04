<?php

declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Tests\Messaging;

use PcComponentes\Ddd\Domain\Model\ValueObject\DateTimeValueObject;
use PcComponentes\Ddd\Domain\Model\ValueObject\Uuid;
use PcComponentes\Ddd\Util\Message\AggregateMessage;
use PcComponentes\Ddd\Util\Message\Message;
use PcComponentes\Ddd\Util\Message\SimpleMessage;
use PcComponentes\Ddd\Util\Message\ValueObject\AggregateId;
use PcComponentes\OpenApiMessagingContext\Messaging\SpyMiddleware;
use PcComponentes\OpenApiMessagingContext\Serialization\SchemaValidatorSimpleMessageSerializable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackMiddleware;

class SpyMiddlewareTest extends TestCase
{
    private SpyMiddleware $spyMiddleware;
    private SchemaValidatorSimpleMessageSerializable $serializer;

    public function setUp(): void
    {
        $this->serializer = new SchemaValidatorSimpleMessageSerializable();
        $this->spyMiddleware = new SpyMiddleware();
        $this->spyMiddleware->reset();
    }

    /** @test */
    public function given_handled_message_when_has_message_then_return_true(): void
    {
        $this->prepareCommandMessage();

        $result = $this->spyMiddleware->hasMessage(CommandFake::messageName());

        self::assertTrue($result);
    }

    /** @test */
    public function given_non_handled_message_when_has_message_then_return_false(): void
    {
        $result = $this->spyMiddleware->hasMessage(CommandFake::messageName());

        self::assertFalse($result);
    }

    /** @test */
    public function given_handled_message_when_reset_then_has_message_false(): void
    {
        $this->prepareCommandMessage();
        $this->spyMiddleware->reset();

        $result = $this->spyMiddleware->hasMessage(CommandFake::messageName());

        self::assertFalse($result);
    }

    /** @test */
    public function given_handled_message_when_get_message_then_return_message(): void
    {
        $command = $this->fakeCommand();
        $this->prepareCustomMessage($command);

        $message = $this->spyMiddleware->getMessage($command::messageName());

        self::assertEquals($this->serializer->serialize($command), $message);
    }

    /** @test */
    public function given_non_handled_message_when_get_message_then_expect_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->spyMiddleware->getMessage(CommandFake::messageName());
    }

    /** @test */
    public function given_multiple_same_handled_message_when_get_all_messages_then_return_all(): void
    {
        $command = $this->fakeCommand();
        $this->prepareCustomMessage($command);
        $this->prepareCustomMessage($command);

        $result = $this->spyMiddleware->getMessagesFromName($command::messageName());

        self::assertCount(2, $result);
        self::assertEquals($this->serializer->serialize($command), $result[0]);
        self::assertEquals($this->serializer->serialize($command), $result[1]);
    }

    /** @test */
    public function given_multiple_same_handled_message_when_count_messages_then_assert_counts(): void
    {
        $command = $this->fakeCommand();
        $this->prepareCustomMessage($command);
        $this->prepareCustomMessage($command);

        $result = $this->spyMiddleware->countMessagesFromName($command::messageName());

        self::assertEquals(2, $result);
    }

    /** @test */
    public function given_non_handled_message_when_count_messages_then_return_zero(): void
    {
        $result = $this->spyMiddleware->countMessagesFromName(CommandFake::messageName());

        self::assertEquals(0, $result);
    }

    /** @test */
    public function given_multiple_distinct_handled_message_when_count_messages_then_assert_counts(): void
    {
        $command = $this->fakeCommand();
        $event = $this->fakeDomainEvent();
        $this->prepareCustomMessage($command);
        $this->prepareCustomMessage($event);

        $resultCommand = $this->spyMiddleware->countMessagesFromName(CommandFake::messageName());
        $resultEvent = $this->spyMiddleware->countMessagesFromName(DomainEventFake::messageName());

        self::assertEquals(1, $resultCommand);
        self::assertEquals(1, $resultEvent);
    }

    private function prepareCustomMessage(Message $message): void
    {
        $this->spyMiddleware->handle(new Envelope($message), new StackMiddleware());
    }

    public function prepareCommandMessage(): void
    {
        $this->spyMiddleware->handle(
            new Envelope($this->fakeCommand()),
            new StackMiddleware(),
        );
    }

    private function fakeCommand(): SimpleMessage
    {
        return CommandFake::fromPayload(
            Uuid::from('efcf7fc2-2d6b-4a52-9763-4472a37b3c23'),
            [],
        );
    }

    private function fakeDomainEvent(): AggregateMessage
    {
        return DomainEventFake::fromPayload(
            Uuid::from('efcf7fc2-2d6b-4a52-9763-4472a37b3c24'),
            AggregateId::from('efcf7fc2-2d6b-4a52-9763-4472a37b3c25'),
            DateTimeValueObject::from('now'),
            [],
        );
    }
}
