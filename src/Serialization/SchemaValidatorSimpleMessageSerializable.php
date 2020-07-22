<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Serialization;

use PcComponentes\Ddd\Util\Message\Serialization\SimpleMessageSerializable;
use PcComponentes\Ddd\Util\Message\SimpleMessage;

final class SchemaValidatorSimpleMessageSerializable implements SimpleMessageSerializable
{
    public function serialize(SimpleMessage $message)
    {
        return \json_encode(
            [
                'message_id' => $message->messageId(),
                'type' => $message::messageName(),
                'attributes' => $message->messagePayload(),
            ]
        );
    }
}
