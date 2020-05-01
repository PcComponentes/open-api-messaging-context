<?php
declare(strict_types=1);

namespace Pccomponentes\OpenApiMessagingContext\Messaging;

use PcComponentes\Ddd\Util\Message\AggregateMessage;
use PcComponentes\Ddd\Util\Message\MessageVisitor;
use PcComponentes\Ddd\Util\Message\SimpleMessage;
use Pccomponentes\OpenApiMessagingContext\Serialization\SchemaValidatorAggregateMessageSerializable;
use Pccomponentes\OpenApiMessagingContext\Serialization\SchemaValidatorSimpleMessageSerializable;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class SpyMiddleware implements MiddlewareInterface, MessageVisitor
{
    private static $messages;
    private $simpleMessageSerializable;
    private $aggregateMessageSerializable;

    public function __construct()
    {
        $this->simpleMessageSerializable = new SchemaValidatorSimpleMessageSerializable();
        $this->aggregateMessageSerializable = new SchemaValidatorAggregateMessageSerializable();
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        /** @var Message $message */
        $message = $envelope->getMessage();
        $message->accept($this);

        return $stack->next()->handle($envelope, $stack);
    }

    private function save($key, $data): void
    {
        self::$messages[$key] = $data;
    }

    public function getMessage(string $name)
    {
        if ($this->hasMessage($name)) {
            return self::$messages[$name];
        }

        throw new \Exception('Message ' . $name . ' not dispatched');
    }

    public function hasMessage(string $name): bool
    {
        return \array_key_exists($name, self::$messages);
    }

    public function reset(): void
    {
        self::$messages = [];
    }

    public function visitSimpleMessage(SimpleMessage $simpleMessage): void
    {
        $data = $this->simpleMessageSerializable->serialize($simpleMessage);
        $this->save($simpleMessage::messageName(), $data);
    }

    public function visitAggregateMessage(AggregateMessage $aggregateMessage): void
    {
        $data = $this->aggregateMessageSerializable->serialize($aggregateMessage);
        $this->save($aggregateMessage::messageName(), $data);
    }
}
