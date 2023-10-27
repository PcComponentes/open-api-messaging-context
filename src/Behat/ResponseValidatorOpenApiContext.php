<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat;

use Behat\Behat\Context\Context;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonSchema;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidationException;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidator;
use PcComponentes\OpenApiMessagingContext\OpenApi\OpenApiSchemaParser;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

abstract class ResponseValidatorOpenApiContext extends ValidatorApiContext implements Context
{
    private const HTTP_NO_CONTENT_CODE = 204;

    public function __construct(private string $rootPath, private CacheInterface $cacheAdapter)
    {
    }

    /** @Then the JSON response should be valid according to OpenApi :dumpPath schema :schema */
    public function theJsonResponseShouldBeValidAccordingToOpenApiSchema($dumpPath, $schema): void
    {
        $path = \realpath($this->rootPath . '/' . $dumpPath);
        $this->checkSchemaFile($path);

        $responseJson = $this->extractResponseContent();

        $allSpec = $this->cacheAdapter->get(
            \md5($path),
            function (ItemInterface $item) use ($path) {
                $item->expiresAfter(null);

                $allSpec = Yaml::parse(file_get_contents($path));

                return $this->getDataExternalReferences($allSpec, $path);
            },
        );

        $schemaSpec = (new OpenApiSchemaParser($allSpec))->parse($schema);

        $validator = new JsonValidator(
            $responseJson,
            new JsonSchema(\json_decode(\json_encode($schemaSpec), false)),
        );
        $validation = $validator->validate();

        if ($validation->hasError()) {
            throw new JsonValidationException($validation->errorMessage());
        }
    }

    /** @Then the request should be valid according to OpenApi :dumpPath with path :openApiPath */
    public function theRequestShouldBeValidAccordingToOpenApiWithPath(string $dumpPath, string $openApiPath): void
    {
        $path = \realpath($this->rootPath . '/' . $dumpPath);
        $this->checkSchemaFile($path);

        $method = \strtolower($this->extractMethod());
        $contentType = $this->extractRequestContentType();

        $requestJson = $this->extractRequestContent();

        $allSpec = $this->cacheAdapter->get(
            \md5($path),
            function (ItemInterface $item) use ($path) {
                $item->expiresAfter(null);

                $allSpec = Yaml::parse(file_get_contents($path));

                return $this->getDataExternalReferences($allSpec, $path);
            },
        );

        $schemaSpec = (new OpenApiSchemaParser($allSpec))->fromRequestBody(
            $openApiPath,
            $method,
            $contentType,
        );

        $validator = new JsonValidator(
            $requestJson,
            new JsonSchema(\json_decode(\json_encode($schemaSpec), false)),
        );
        $validation = $validator->validate();

        if ($validation->hasError()) {
            throw new JsonValidationException($validation->errorMessage());
        }
    }

    /** @Then the response should be valid according to OpenApi :dumpPath with path :openApiPath */
    public function theResponseShouldBeValidAccordingToOpenApiWithPath(string $dumpPath, string $openApiPath): void
    {
        $path = \realpath($this->rootPath . '/' . $dumpPath);
        $this->checkSchemaFile($path);

        $statusCode = $this->extractStatusCode();
        $method = \strtolower($this->extractMethod());
        $contentType = $this->responseContentType();

        $responseJson = $this->extractResponseContent();

        $allSpec = $this->cacheAdapter->get(
            \md5($path),
            function (ItemInterface $item) use ($path) {
                $item->expiresAfter(null);

                $allSpec = Yaml::parse(file_get_contents($path));

                return $this->getDataExternalReferences($allSpec, $path);
            },
        );

        $schemaSpec = (new OpenApiSchemaParser($allSpec))->fromResponse(
            $openApiPath,
            $method,
            $statusCode,
            $contentType,
        );

        $validator = new JsonValidator(
            $responseJson,
            new JsonSchema(\json_decode(\json_encode($schemaSpec), false)),
        );
        $validation = $validator->validate();

        if ($validation->hasError()) {
            throw new JsonValidationException($validation->errorMessage());
        }
    }

    /** @Then the request and response should be valid according to OpenApi :dumpPath with path :openApiPath */
    public function theRequestAndResponseShouldBeValidAccordingToOpenApiWithPath(
        string $dumpPath,
        string $openApiPath,
    ): void {
        $this->theRequestShouldBeValidAccordingToOpenApiWithPath($dumpPath, $openApiPath);

        $this->theResponseShouldBeValidAccordingToOpenApiWithPath($dumpPath, $openApiPath);
    }

    abstract protected function extractMethod(): string;

    abstract protected function extractRequestContentType(): ?string;

    abstract protected function extractResponseContentType(): ?string;

    abstract protected function extractStatusCode(): int;

    abstract protected function extractRequestContent(): string;

    abstract protected function extractResponseContent(): string;

    private function responseContentType(): string
    {
        if (self::HTTP_NO_CONTENT_CODE === $this->extractStatusCode()) {
            return '';
        }

        $contentType = $this->extractResponseContentType();

        if (null === $contentType) {
            throw new \RuntimeException(
                'HTTP content-type response header key not defined',
            );
        }

        return $contentType;
    }
}
