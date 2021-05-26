<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

final class OpenApiSchemaParser
{
    private array $originalContent;

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

    public function fromResponse(string $path, string $method, int $statusCode, string $contentType): array
    {
        $rootPaths = $this->originalContent['paths'];
        $this->assertPathRoot($path, $rootPaths);
        $pathRoot = $rootPaths[$path];

        $this->assertMethodRoot($path, $method, $pathRoot);
        $methodRoot = $pathRoot[$method];

        $this->assertStatusCodeRoot($path, $method, $statusCode, $methodRoot);
        $statusCodeRoot = $methodRoot['responses'][$statusCode];

        if (false === \array_key_exists('content', $statusCodeRoot)) {
            return [];
        }

        $this->assertContentTypeRoot($path, $method, $statusCode, $contentType, $statusCodeRoot);
        return $this->extractData($statusCodeRoot['content'][$contentType]['schema']);
    }

    public function fromRequest(string $path, string $method, string $contentType): array
    {
        $rootPaths = $this->originalContent['paths'];
        $this->assertPathRoot($path, $rootPaths);
        $pathRoot = $rootPaths[$path];

        $this->assertMethodRoot($path, $method, $pathRoot);
        $methodRoot = $pathRoot[$method];

        if (false === \array_key_exists('requestBody', $methodRoot)) {
            return [];
        }

        $requestBodyRoot = $methodRoot['requestBody'];

        return $this->extractData($requestBodyRoot['content'][$contentType]['schema']);
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
        $explodedDef = \explode('/', $cleanDef);
        $foundDef = \array_reduce($explodedDef, function ($last, $elem) {
            return null === $last ? $this->originalContent[$elem] : $last[$elem];
        });

        return $this->extractData(\array_key_exists('payload', $foundDef) ? $foundDef['payload'] : $foundDef);
    }

    private function assertPathRoot(string $path, $rootPaths): void
    {
        if (false === \array_key_exists($path, $rootPaths)) {
            throw new \InvalidArgumentException(\sprintf('%s path not found', $path));
        }
    }

    private function assertMethodRoot(string $path, string $method, $pathRoot): void
    {
        if (false === \array_key_exists($method, $pathRoot)) {
            throw new \InvalidArgumentException(\sprintf('%s method not found on %s', $method, $path));
        }
    }

    private function assertStatusCodeRoot(string $path, string $method, int $statusCode, $methodRoot): void
    {
        if (false === \array_key_exists('responses', $methodRoot) || false === \array_key_exists(
            $statusCode,
            $methodRoot['responses']
        )) {
            throw new \InvalidArgumentException(
                \sprintf('%s response not found on %s path with %s method', $statusCode, $path, $method)
            );
        }
    }

    private function assertContentTypeRoot(
        string $path,
        string $method,
        int $statusCode,
        string $contentType,
        $statusCodeRoot
    ): void {
        if (false === \array_key_exists($contentType, $statusCodeRoot['content'])) {
            throw new \InvalidArgumentException(
                \sprintf(
                    '%s content-type not found on %s path with %s method with %s statusCode',
                    $contentType,
                    $path,
                    $method,
                    $statusCode
                )
            );
        }
    }
}
