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
