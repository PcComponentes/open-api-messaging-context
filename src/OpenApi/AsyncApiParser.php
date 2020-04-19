<?php
declare(strict_types=1);

namespace Pccomponentes\OpenApiMessagingContext\OpenApi;

final class AsyncApiParser
{
    private $originalContent;

    public function __construct(array $originalContent)
    {
        $this->originalContent = $originalContent;
    }

    public function parse($name): array
    {
        $topicName = $name;
        $baseTopic = \array_key_exists('baseTopic', $this->originalContent) ? $this->originalContent['baseTopic'] : '';
        if ('' !== $baseTopic) {
            $topicName = \preg_replace('/^' . $baseTopic . '\./', '', $topicName);
        }

        if (false === \array_key_exists($topicName, $this->originalContent['topics'])) {
            throw new \Exception(\sprintf('Topic with name <%s> not found', $topicName));
        }

        $topic = $this->originalContent['topics'][$topicName]['publish'];

        return $this->extractData($topic);
    }

    private function extractData(array $data): array
    {
        $aux = [];
        foreach ($data as $key => $elem) {
            if ($key === '$ref') {
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
        $explodedDef = \explode('/', $cleanDef);
        $foundDef = \array_reduce($explodedDef, function ($last, $elem) {
            return null === $last ? $this->originalContent[$elem] : $last[$elem];
        });

        return $this->extractData(\array_key_exists('payload', $foundDef) ? $foundDef['payload'] : $foundDef);
    }
}
