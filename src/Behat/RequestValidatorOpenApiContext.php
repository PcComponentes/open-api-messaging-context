<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use PcComponentes\OpenApiMessagingContext\OpenApi\RequestBodySchemaParser;
use PcComponentes\OpenApiMessagingContext\OpenApi\UrlParameterSchemaParser;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Behat\MinkExtension\Context\MinkContext;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonSchema;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidationException;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidator;
use Symfony\Component\Yaml\Yaml;

final class RequestValidatorOpenApiContext implements Context
{
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
     * @Then I send a :method request to :url and request should be valid according to OpenApi :openApiPath
     */
    public function theJsonRequestShouldBeValidAccordingToOpenApiSchema(
        $method,
        $url,
        $openApiPath,
        PyStringNode $requestBody = null
    ): void
    {
        $fullPath = \realpath($this->rootPath . '/' . $openApiPath);
        $this->checkSchemaFile($fullPath);

        $contentType = 'application/json';
        $method = \strtolower($method);

        $allSpec = Yaml::parse(\file_get_contents($fullPath));

        $this->validateUrlParameters($allSpec, $url, $method);

        $this->validateRequestBody($allSpec, $url, $method, $contentType, $requestBody);

        /** @var HttpKernelBrowser $client */
        $client = $this->minkContext->getSession()->getDriver()->getClient();

        $postParams = [];
        $files = [];
        $serverParams = [];

        $client->request(
            $method,
            $url,
            $postParams,
            $files,
            $serverParams,
            $requestBody->getRaw(),
        );
    }

    private function checkSchemaFile($filename): void
    {
        if (false === \is_file($filename)) {
            throw new \RuntimeException(
                'The JSON schema doesn\'t exist'
            );
        }
    }

    private function validateUrlParameters($allSpec, string $url, string $method): void
    {
        $parser = new UrlParameterSchemaParser($allSpec);
        $schema = $parser->parse($url, $method);

        $urlValidator  = new JsonValidator(
            \json_encode($schema->parameters()),
            new JsonSchema($schema->schema()),
        );

        $validation = $urlValidator->validate();

        if (true === $validation->hasError()) {
            throw new JsonValidationException($validation->errorMessage());
        }
    }

    private function validateRequestBody(
        array $allSpec,
        string $url,
        string $method,
        string $contentType,
        ?PyStringNode $requestBody
    ): void
    {
        $parser = new RequestBodySchemaParser($allSpec);
        $schema = $parser->parse($url, $method, $contentType);

        $jsonSchema = new JsonSchema($schema);
        $validator = new JsonValidator($requestBody->getRaw(), $jsonSchema);
        $validation = $validator->validate();

        if (true === $validation->hasError()) {
            throw new JsonValidationException($validation->errorMessage());
        }
    }
}
