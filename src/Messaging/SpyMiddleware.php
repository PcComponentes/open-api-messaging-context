<?php

namespace Pccomponentes\OpenApiMessagingContext\Messaging;

use Pccomponentes\Amqp\Messenger\MessageSerializer;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class SpyMiddleware implements MiddlewareInterface
{
    private static $messages;
    private $serializer;

    public function __construct(MessageSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $serialized = $this->serializer->serialize($envelope->getMessage());
        $serialized = \json_encode(\json_decode($serialized)->data);
        self::$messages[$this->serializer->routingKey($envelope->getMessage())] = $serialized;

        return $stack->next()->handle($envelope, $stack);
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

    public function reset()
    {
        self::$messages = [];
    }
}
