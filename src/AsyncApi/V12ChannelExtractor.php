<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\AsyncApi;

final class V12ChannelExtractor implements ChannelExtractor
{
    public function extract(array $originalContent, string $channel): array
    {
        $topicName = $channel;
        $baseTopic = \array_key_exists('baseTopic', $originalContent)
            ? $originalContent['baseTopic']
            : '';

        if ('' !== $baseTopic) {
            $topicName = \preg_replace('/^' . $baseTopic . '\./', '', $topicName);
        }

        if (false === \array_key_exists($topicName, $originalContent['topics'])) {
            throw new \InvalidArgumentException(\sprintf('Topic with name <%s> not found', $topicName));
        }

        return $originalContent['topics'][$topicName]['publish'];
    }
}
