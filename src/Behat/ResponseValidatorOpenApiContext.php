<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat;

use Behat\Behat\Context\Context;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonSchema;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidationException;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidator;
use PcComponentes\OpenApiMessagingContext\OpenApi\OpenApiSchemaParser;
use Symfony\Component\Yaml\Yaml;

abstract class ResponseValidatorOpenApiContext implements Context
{
    private const HTTP_NO_CONTENT_CODE = 204;

    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @Then the JSON response should be valid according to OpenApi :dumpPath schema :schema
     */
    public function theJsonResponseShouldBeValidAccordingToOpenApiSchema($dumpPath, $schema): void
    {
        $path = \realpath($this->rootPath . '/' . $dumpPath);
        $this->checkSchemaFile($path);

        $responseJson = $this->extractContent();

        $allSpec = Yaml::parse(file_get_contents($path));
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

    /**
     * @Then the response should be valid according to OpenApi :dumpPath with path :openApiPath
     */
    public function theResponseShouldBeValidAccordingToOpenApiWithPath(string $dumpPath, string $openApiPath): void
    {
        $path = \realpath($this->rootPath . '/' . $dumpPath);
        $this->checkSchemaFile($path);

        $statusCode = $this->extractStatusCode();
        $method = \strtolower($this->extractMethod());
        $contentType = $this->contentType();

        $responseJson = $this->extractContent();

        $allSpec = Yaml::parse(\file_get_contents($path));
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

    abstract protected function extractMethod(): string;

    abstract protected function extractContentType(): ?string;

    abstract protected function extractStatusCode(): int;

    abstract protected function extractContent(): string;

    private function checkSchemaFile($filename): void
    {
        if (false === \is_file($filename)) {
            throw new \RuntimeException(
                "The JSON schema doesn't exist",
            );
        }
    }

    private function contentType(): string
    {
        if (self::HTTP_NO_CONTENT_CODE === $this->extractStatusCode()) {
            return '';
        }

        $contentType = $this->extractContentType();

        if (null === $contentType) {
            throw new \RuntimeException(
                'HTTP content-type response header key not defined',
            );
        }

        return $contentType;
    }
}
