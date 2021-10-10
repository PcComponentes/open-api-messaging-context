<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat;

use Assert\Assert;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use PcComponentes\Ddd\Util\Message\Serialization\JsonApi\SimpleMessageStream;
use PcComponentes\Ddd\Util\Message\Serialization\SimpleMessageUnserializable;
use Symfony\Component\Messenger\MessageBusInterface;

final class SimpleMessageContext implements Context
{
    private MessageBusInterface $bus;
    private SimpleMessageUnserializable $simpleMessageUnserializable;
    private \Throwable $lastException;

    public function __construct(
        MessageBusInterface $bus,
        SimpleMessageUnserializable $simpleMessageUnserializable
    ) {
        $this->bus = $bus;
        $this->simpleMessageUnserializable = $simpleMessageUnserializable;
    }

    /**
     * @When I receive a simple message with payload:
     */
    public function dispatchMessage(PyStringNode $payload): void
    {
        $message = $this->simpleMessageUnserializable->unserialize(
            $this->payloadToStream($payload->getRaw())
        );
        $this->bus->dispatch($message);
    }

    /**
     * @When I receive a simple failing message with payload:
     */
    public function dispatchFailingMessage(PyStringNode $payload): void
    {
        try {
            $this->dispatchMessage($payload);
        } catch (\Throwable $exception) {
            $this->lastException = $exception;

            return;
        }

        $message = 'Expecting a failed message';

        throw new \Exception($message);
    }

    /**
     * @Then exception class for the last message should be :exceptionType
     */
    public function checkExceptionClass($exceptionType): void
    {
        if (get_class($this->lastException) === $exceptionType) {
            return;
        }

        $message = \sprintf(
            'Expecting a failed message with exception \'%s\' but got \'%s\'',
            $exceptionType,
            get_class($this->lastException),
        );

        throw new \Exception($message);
    }
    
    private function payloadToStream(string $rawPayload): SimpleMessageStream
    {
        $payload = \json_decode($rawPayload, true, 512, \JSON_THROW_ON_ERROR);
        $this->assertContent($payload);

        $body = $payload['data'];

        return new SimpleMessageStream(
            $body['message_id'],
            $body['type'],
            \json_encode($body['attributes'], \JSON_THROW_ON_ERROR, 512),
        );
    }

    private function assertContent(array $content): void
    {
        Assert::lazy()->tryAll()
            ->that($content['data'], 'data')->isArray()
            ->keyExists('message_id')
            ->keyExists('type')
            ->keyExists('attributes')
            ->verifyNow();

        Assert::lazy()->tryAll()
            ->that($content['data']['message_id'], 'message_id')->uuid()
            ->that($content['data']['type'], 'type')->string()->notEmpty()
            ->that($content['data']['attributes'], 'attributes')->isArray()
            ->verifyNow();
    }
}
