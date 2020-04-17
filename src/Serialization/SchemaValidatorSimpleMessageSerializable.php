<?php declare(strict_types=1);

namespace Pccomponentes\OpenApiMessagingContext\Serialization;

use Pccomponentes\Ddd\Util\Message\Serialization\SimpleMessageSerializable;
use Pccomponentes\Ddd\Util\Message\SimpleMessage;

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
