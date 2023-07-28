<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Serialization;

use PcComponentes\Ddd\Util\Message\AggregateMessage;
use PcComponentes\Ddd\Util\Message\Serialization\AggregateMessageSerializable;

final class SchemaValidatorAggregateMessageSerializable implements AggregateMessageSerializable
{
    public function serialize(AggregateMessage $message)
    {
        return \json_encode(
            [
                'message_id' => $message->messageId(),
                'type' => $message::messageName(),
                'occurred_on' => $message->occurredOn()->getTimestamp(),
                'attributes' => \array_merge(
                    ['aggregate_id' => $message->aggregateId()->value()],
                    $message->messagePayload(),
                ),
            ],
        );
    }
}
