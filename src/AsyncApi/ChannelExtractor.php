<?php declare(strict_types=1);

namespace Pccomponentes\OpenApiMessagingContext\AsyncApi;

interface ChannelExtractor
{
    public function extract(array $originalContent, string $channel): array;
}
