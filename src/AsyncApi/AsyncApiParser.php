<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\AsyncApi;

final class AsyncApiParser
{
    private array $versionExtractor;

    public function __construct(private array $originalContent)
    {
        $this->versionExtractor = [
            '1.2' => new V12ChannelExtractor(),
            '2.0' => new V20ChannelExtractor(),
        ];
    }

    public function parse($name): array
    {
        $channelExtractor = $this->extractVersion();

        return $this->extractData($channelExtractor->extract($this->originalContent, $name));
    }

    private function extractVersion(): ChannelExtractor
    {
        if (false === \array_key_exists('asyncapi', $this->originalContent)) {
            throw new \RuntimeException('Unable to find asyncapi document version');
        }

        if (1 === \preg_match('/^1\.2/', $this->originalContent['asyncapi'])) {
            return $this->versionExtractor['1.2'];
        }

        if (1 === \preg_match('/^2\.0/', $this->originalContent['asyncapi'])) {
            return $this->versionExtractor['2.0'];
        }

        throw new \InvalidArgumentException(
            \sprintf('%s async api version not supported', $this->originalContent['asyncapi']),
        );
    }

    private function extractData(array $data): array
    {
        $aux = [];

        foreach ($data as $key => $elem) {
            if ('$ref' === $key) {
                $aux = $this->findDefinition($elem);

                continue;
            }

            if (\is_array($elem)) {
                $aux[$key] = $this->extractData($elem);

                continue;
            }

            $aux[$key] = $elem;
        }

        return $aux;
    }

    private function findDefinition(string $def): array
    {
        $cleanDef = \preg_replace('/^\#\//', '', $def);

        if (false !== \strpos($cleanDef, '.yaml')) {
            [$filename, $refDef] = \explode('#/', $cleanDef);
            $allSpec = $this->originalContent[$filename];

            return (new self($allSpec))->extractData(['$ref' => $refDef]);
        }

        $explodedDef = \explode('/', $cleanDef);
        $foundDef = \array_reduce(
            $explodedDef,
            fn ($last, $elem) => null === $last
                ? $this->originalContent[$elem]
                : $last[$elem],
        );

        return $this->extractData(\array_key_exists('payload', $foundDef) ? $foundDef['payload'] : $foundDef);
    }
}
