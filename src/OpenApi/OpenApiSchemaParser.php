<?php declare(strict_types=1);

namespace Pccomponentes\OpenApiMessagingContext\OpenApi;

final class OpenApiSchemaParser
{
    private $originalContent;

    public function __construct(array $originalContent)
    {
        $this->originalContent = $originalContent;
    }

    public function parse($name): array
    {
        $schemaSpec = $this->originalContent['components']['schemas'][$name];
        if (null === $schemaSpec) {
            throw new \InvalidArgumentException(\sprintf('%s schema not found', $name));
        }
        return $this->extractData($schemaSpec);
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
            return (null === $last) ? $this->originalContent[$elem] : $last[$elem];
        });

        return $this->extractData(\array_key_exists('payload', $foundDef) ? $foundDef['payload'] : $foundDef);
    }
}
