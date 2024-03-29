<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

final class OpenApiSchemaParser
{
    public function __construct(private array $originalContent)
    {
    }

    public function parse($name): array
    {
        $schemaSpec = $this->originalContent['components']['schemas'][$name];

        if (null === $schemaSpec) {
            throw new \InvalidArgumentException(\sprintf('%s schema not found', $name));
        }

        return $this->extractData($schemaSpec);
    }

    public function fromRequestBody(string $path, string $method, string $contentType): array
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
        $this->assertRequestContentTypeRoot($path, $method, $contentType, $requestBodyRoot);

        return $this->extractData($requestBodyRoot['content'][$contentType]['schema']);
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

        $this->assertResponseContentTypeRoot($path, $method, $statusCode, $contentType, $statusCodeRoot);

        return $this->extractData($statusCodeRoot['content'][$contentType]['schema']);
    }

    private function extractData(array $data): array
    {
        $aux = [];

        $data = $this->convertNullableTypeToJsonSchema($data);

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
                $methodRoot['responses'],
            )) {
            throw new \InvalidArgumentException(
                \sprintf('%s response not found on %s path with %s method', $statusCode, $path, $method),
            );
        }
    }

    private function assertRequestContentTypeRoot(
        string $path,
        string $method,
        string $contentType,
        $statusCodeRoot,
    ): void {
        if (false === \array_key_exists($contentType, $statusCodeRoot['content'])) {
            throw new \InvalidArgumentException(
                \sprintf(
                    '%s content-type not found on %s path with %s method',
                    $contentType,
                    $path,
                    $method,
                ),
            );
        }
    }

    private function assertResponseContentTypeRoot(
        string $path,
        string $method,
        int $statusCode,
        string $contentType,
        $statusCodeRoot,
    ): void {
        if (false === \array_key_exists($contentType, $statusCodeRoot['content'])) {
            throw new \InvalidArgumentException(
                \sprintf(
                    '%s content-type not found on %s path with %s method with %s statusCode',
                    $contentType,
                    $path,
                    $method,
                    $statusCode,
                ),
            );
        }
    }

    private function convertNullableTypeToJsonSchema(array $data): array
    {
        if (false === \array_key_exists('nullable', $data)) {
            return $data;
        }

        $data['type'] = \is_array($data['type'])
            ? \array_merge($data['type'], ['null'])
            : [$data['type'], 'null'];

        unset($data['nullable']);

        return $data;
    }
}
