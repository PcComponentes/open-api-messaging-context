<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonSchema;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidationException;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidator;
use PcComponentes\OpenApiMessagingContext\OpenApi\OpenApiSchemaParser;
use Symfony\Component\Yaml\Yaml;

final class ResponseValidatorOpenApiContext implements Context
{
    private const CONTENT_TYPE_RESPONSE_HEADER_KEY = 'content-type';
    private const HTTP_NO_CONTENT_CODE = 204;

    private MinkContext $minkContext;
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @BeforeScenario
     */
    public function bootstrapEnvironment(BeforeScenarioScope $scope): void
    {
        $this->minkContext = $scope->getEnvironment()->getContext(MinkContext::class);
    }

    /**
     * @Then the JSON response should be valid according to OpenApi :dumpPath schema :schema
     */
    public function theJsonResponseShouldBeValidAccordingToOpenApiSchema($dumpPath, $schema): void
    {
        $path = realpath($this->rootPath . '/' . $dumpPath);
        $this->checkSchemaFile($path);

        $responseJson = $this->minkContext->getSession()->getPage()->getContent();

        $allSpec = Yaml::parse(file_get_contents($path));
        $schemaSpec = (new OpenApiSchemaParser($allSpec))->parse($schema);

        $validator = new JsonValidator($responseJson, new JsonSchema(\json_decode(\json_encode($schemaSpec), false)));
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
        $method = $this->extractMethod();
        $contentType = $this->extractContentType();

        $responseJson = $this->minkContext->getSession()->getPage()->getContent();

        $allSpec = Yaml::parse(\file_get_contents($path));
        $schemaSpec = (new OpenApiSchemaParser($allSpec))->fromResponse($openApiPath, $method, $statusCode,
            $contentType);

        $validator = new JsonValidator($responseJson, new JsonSchema(\json_decode(\json_encode($schemaSpec), false)));
        $validation = $validator->validate();

        if ($validation->hasError()) {
            throw new JsonValidationException($validation->errorMessage());
        }
    }

    private function checkSchemaFile($filename): void
    {
        if (false === \is_file($filename)) {
            throw new \RuntimeException(
                'The JSON schema doesn\'t exist'
            );
        }
    }

    private function extractMethod(): string
    {
        /** @var Client $requestClient */
        $requestClient = $this->minkContext->getSession()->getDriver()->getClient();
        $method = $requestClient->getHistory()->current()->getMethod();

        return \strtolower($method);
    }

    private function extractStatusCode(): int
    {
        return $this->minkContext->getSession()->getStatusCode();
    }

    private function extractContentType(): string
    {
        if (self::HTTP_NO_CONTENT_CODE === $this->extractStatusCode()) {
            return '';
        }

        $contentType = $this->minkContext->getSession()->getResponseHeader(self::CONTENT_TYPE_RESPONSE_HEADER_KEY);

        if (null === $contentType) {
            throw new \RuntimeException(
                'HTTP content-type response header key not defined'
            );
        }

        return $contentType;
    }
}
