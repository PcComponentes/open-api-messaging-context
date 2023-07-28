<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\AsyncApi;

final class V20ChannelExtractor implements ChannelExtractor
{
    public function extract(array $originalContent, string $channel): array
    {
        if (false === \array_key_exists($channel, $originalContent['channels'])) {
            throw new \InvalidArgumentException(\sprintf('Topic with name <%s> not found', $channel));
        }

        return $originalContent['channels'][$channel]['publish']['message'];
    }
}
