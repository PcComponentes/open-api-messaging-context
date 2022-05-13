<?php

declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Tests\Behat;

use PcComponentes\Ddd\Domain\Model\ValueObject\DateTimeValueObject;
use PcComponentes\Ddd\Domain\Model\ValueObject\Uuid;
use PcComponentes\Ddd\Util\Message\AggregateMessage;
use PcComponentes\Ddd\Util\Message\Message;
use PcComponentes\OpenApiMessagingContext\Behat\MessageValidatorOpenApiContext;
use PcComponentes\OpenApiMessagingContext\Messaging\SpyMiddleware;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidationException;
use PcComponentes\OpenApiMessagingContext\Tests\Messaging\DomainEventFake;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackMiddleware;

class MessageValidatorOpenApiContextTest extends TestCase
{
    private MessageValidatorOpenApiContext $messageValidatorOpenApiContext;
    private SpyMiddleware $spyMiddleware;

    protected function setUp(): void
    {
        $this->spyMiddleware = new SpyMiddleware();
        $this->messageValidatorOpenApiContext = new MessageValidatorOpenApiContext(
            __DIR__,
            $this->spyMiddleware,
        );
        $this->spyMiddleware->reset();
    }

    /** @test */
    public function given_correct_json_and_openapi_when_validate_then_ok(): void
    {
        $this->expectNotToPerformAssertions();
        $this->prepareMessage(
            $this->fakeDomainEvent(
                [
                    'some_attribute' => 'some_string',
                ],
            ),
        );

        $this->messageValidatorOpenApiContext->theMessageShouldBeValidAccordingToTheSwagger(
            DomainEventFake::messageName(),
            '../AsyncApi/valid-asyncapi-v20-spec.yaml',
        );
    }

    /** @test */
    public function given_incorrect_json_and_openapi_when_validate_then_validation_exception(): void
    {
        $this->expectException(JsonValidationException::class);
        $this->prepareMessage(
            $this->fakeDomainEvent(
                [
                    'foo_attribute' => 'bar',
                ],
            ),
        );

        $this->messageValidatorOpenApiContext->theMessageShouldBeValidAccordingToTheSwagger(
            DomainEventFake::messageName(),
            '../AsyncApi/valid-asyncapi-v20-spec.yaml',
        );
    }

    /** @test */
    public function given_one_message_when_count_messages_then_ok(): void
    {
        $this->expectNotToPerformAssertions();
        $this->prepareMessage($this->fakeDomainEvent([]));

        $this->messageValidatorOpenApiContext->theMessageShouldBeDispatchedManyTimes(DomainEventFake::messageName(), 1);
    }

    /** @test */
    public function given_multiple_message_when_count_messages_then_ok(): void
    {
        $this->expectNotToPerformAssertions();
        $this->prepareMessage($this->fakeDomainEvent([]));
        $this->prepareMessage($this->fakeDomainEvent([]));
        $this->prepareMessage($this->fakeDomainEvent([]));

        $this->messageValidatorOpenApiContext->theMessageShouldBeDispatchedManyTimes(DomainEventFake::messageName(), 3);
    }

    /** @test */
    public function given_multiple_message_when_count_wrong_messages_then_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->prepareMessage($this->fakeDomainEvent([]));

        $this->messageValidatorOpenApiContext->theMessageShouldBeDispatchedManyTimes(DomainEventFake::messageName(), 3);
    }

    public function prepareMessage(Message $message): void
    {
        $this->spyMiddleware->handle(
            new Envelope($message),
            new StackMiddleware(),
        );
    }

    private function fakeDomainEvent(array $attributes): AggregateMessage
    {
        return DomainEventFake::fromPayload(
            Uuid::from('efcf7fc2-2d6b-4a52-9763-4472a37b3c24'),
            Uuid::from('efcf7fc2-2d6b-4a52-9763-4472a37b3c25'),
            DateTimeValueObject::from('now'),
            $attributes,
        );
    }
}
