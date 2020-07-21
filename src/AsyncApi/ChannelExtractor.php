<?php declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\AsyncApi;

interface ChannelExtractor
{
    public function extract(array $originalContent, string $channel): array;
}
