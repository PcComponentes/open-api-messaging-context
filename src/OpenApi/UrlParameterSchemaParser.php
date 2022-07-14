<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class UrlParameterSchemaParser
{
    private array $originalContent;

    public function __construct(array $originalContent)
    {
        $this->originalContent = $originalContent;
    }

    public function parse(string $path, string $method): UrlParameterSchema
    {
        if (false === \array_key_exists('paths', $this->originalContent)){
            throw new \InvalidArgumentException('Malformed OpenAPI spec!');
        }

        $urlParameterSchema = new UrlParameterSchema();
        $urlParameterSchema->setPath($path);
        $urlParameterSchema->setMethod($method);

        $rootPaths = $this->originalContent['paths'];

        $routeCollection = new RouteCollection();
        foreach($rootPaths as $keyPath => $pathData){
            $routeCollection->add($keyPath, new Route($keyPath));
        }

        $urlMatcher = new UrlMatcher($routeCollection, new RequestContext());
        try {
            $urlMatched = $urlMatcher->match($path);
        } catch (ResourceNotFoundException $exception) {
            throw new \InvalidArgumentException(\sprintf('"%s" path not found in OpenAPI', $path));
        }

        $parameters = [];

        foreach ($urlMatched as $key => $value) {
            if ($key === '_route') {
                continue;
            }
            $parameters[$key] = $value;
        }

        $urlParameterSchema->setParameters($parameters);

        $urlParameterSchema->setRoute($urlMatched['_route']);

        $pathRoot = $rootPaths[$urlParameterSchema->route()];

        $this->assertMethodRoot($path, $method, $pathRoot);
        $methodRoot = $pathRoot[$method];

        $schema = $this->extractUrlParameters($methodRoot, $urlMatched);

        $urlParameterSchema->setSchema($schema);

        return $urlParameterSchema;
    }

    private function assertMethodRoot(string $path, string $method, $pathRoot): void
    {
        if (false === \array_key_exists($method, $pathRoot)) {
            throw new \InvalidArgumentException(\sprintf('"%s" method not found for "%s"', $method, $path));
        }
    }

    private function extractUrlParameters(array $methodRoot, array $urlMatched): array
    {
        if (false === \array_key_exists('parameters', $methodRoot)){
            return [];
        }

        $parametersRoot = $methodRoot['parameters'];

        $schema = [];
        $schema['type'] = 'object';
        $schema['properties'] = [];

        foreach ($parametersRoot as $parameter) {
            if ('path' !== $parameter['in']) {
                continue;
            }

            $parameterName = $parameter['name'];

            if (false === \array_key_exists($parameterName, $urlMatched)) {
                throw new \InvalidArgumentException(
                    \sprintf(
                        'Type not specified in OpenAPI for url "%s" and parameter path not found in OpenAPI',
                        $urlMatched['_route'],
                    ),
                );
            }

            $schema['properties'][$parameterName] = [];

            if (true === \array_key_exists('schema', $parameter)) {
                $schema['properties'][$parameterName]['type'] = $parameter['schema']['type'];
                $schema['properties'][$parameterName]['required'] = $parameter['schema']['required'] ?? true;
            }
        }

        return $schema;
    }
}
