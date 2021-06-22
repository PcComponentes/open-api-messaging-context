<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class RequestBodySchemaParser
{
    private array $originalContent;

    public function __construct(array $originalContent)
    {
        $this->originalContent = $originalContent;
    }

    public function parse(string $path, string $method, string $contentType): array
    {
        if (false === \array_key_exists('paths', $this->originalContent)){
            return [];
        }

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

        $pathRoot = $rootPaths[$urlMatched['_route']];

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

    private function assertMethodRoot(string $path, string $method, $pathRoot): void
    {
        if (false === \array_key_exists($method, $pathRoot)) {
            throw new \InvalidArgumentException(\sprintf('%s method not found on %s', $method, $path));
        }
    }
}
