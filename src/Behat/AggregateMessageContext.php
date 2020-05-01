<?php
declare(strict_types=1);

namespace Pccomponentes\OpenApiMessagingContext\Behat;

use Assert\Assert;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use PcComponentes\Ddd\Util\Message\Serialization\AggregateMessageUnserializable;
use PcComponentes\Ddd\Util\Message\Serialization\JsonApi\AggregateMessageStream;
use Symfony\Component\Messenger\MessageBusInterface;

final class AggregateMessageContext implements Context
{
    private MessageBusInterface $bus;
    private AggregateMessageUnserializable $aggregateMessageUnserializable;

    public function __construct(
        MessageBusInterface $bus,
        AggregateMessageUnserializable $aggregateMessageUnserializable
    ) {
        $this->bus = $bus;
        $this->aggregateMessageUnserializable = $aggregateMessageUnserializable;
    }

    /**
     * @When I receive an aggregate message with payload:
     */
    public function dispatchMessage(PyStringNode $payload): void
    {
        $message = $this->aggregateMessageUnserializable->unserialize(
            $this->payloadToStream($payload->getRaw())
        );
        $this->bus->dispatch($message);
    }

    private function payloadToStream(string $rawPayload): AggregateMessageStream
    {
        $payload = \json_decode($rawPayload, true, 512, \JSON_THROW_ON_ERROR);
        $this->assertContent($payload);

        $body = $payload['data'];

        return new AggregateMessageStream(
            $body['message_id'],
            $body['attributes']['aggregate_id'],
            (int) $body['occurred_on'],
            $body['type'],
            0,
            \json_encode($body['attributes'], \JSON_THROW_ON_ERROR, 512),
        );
    }

    private function assertContent(array $content): void
    {
        Assert::lazy()->tryAll()
            ->that($content['data'], 'data')->isArray()
            ->keyExists('message_id')
            ->keyExists('type')
            ->keyExists('occurred_on')
            ->keyExists('attributes')
            ->verifyNow();

        Assert::lazy()->tryAll()
            ->that($content['data']['message_id'], 'message_id')->uuid()
            ->that($content['data']['type'], 'type')->string()->notEmpty()
            ->that($content['data']['occurred_on'], 'occurred_on')->notEmpty()
            ->that($content['data']['attributes'], 'attributes')->isArray()->keyExists('aggregate_id')
            ->verifyNow();

        Assert::lazy()->tryAll()
            ->that($content['data']['attributes']['aggregate_id'], 'aggregate_id')->uuid()
            ->verifyNow();
    }
}
