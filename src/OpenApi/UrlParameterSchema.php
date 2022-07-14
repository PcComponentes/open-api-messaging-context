<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

final class UrlParameterSchema
{
    private string $route;
    private string $path;
    private string $method;

    private array $schema;
    private array $parameters;

    public function schema(): array
    {
        return $this->schema;
    }

    public function setSchema(array $schema): void
    {
        $this->schema = $schema;
    }

    public function route(): string
    {
        return $this->route;
    }
    public function setRoute(string $route): void
    {
        $this->route = $route;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}
