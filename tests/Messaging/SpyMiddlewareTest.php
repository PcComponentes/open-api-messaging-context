<?php

declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Tests\Messaging;

use PcComponentes\Ddd\Domain\Model\ValueObject\Uuid;
use PcComponentes\Ddd\Util\Message\SimpleMessage;
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
        $this->prepareCustomCommand($command);

        $message = $this->spyMiddleware->getMessage($command::messageName());

        self::assertEquals($this->serializer->serialize($command), $message);
    }

    /** @test */
    public function given_non_handled_message_when_get_message_then_expect_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->spyMiddleware->getMessage(CommandFake::messageName());
    }

    private function prepareCustomCommand(SimpleMessage $message): void
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
}
