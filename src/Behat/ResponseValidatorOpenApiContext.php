<?php declare(strict_types=1);

namespace Pccomponentes\OpenApiMessagingContext\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Pccomponentes\OpenApiMessagingContext\OpenApi\JsonSchema;
use Pccomponentes\OpenApiMessagingContext\OpenApi\OpenApiSchemaParser;
use Symfony\Component\Yaml\Yaml;

final class ResponseValidatorOpenApiContext implements Context
{
    /** @var MinkContext */
    private $minkContext;
    private $rootPath;

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

        $this->validate($responseJson, new JsonSchema(\json_decode(\json_encode($schemaSpec), false)));
    }

    private function checkSchemaFile($filename): void
    {
        if (false === is_file($filename)) {
            throw new \RuntimeException(
                'The JSON schema doesn\'t exist'
            );
        }
    }

    private function validate(string $json, JsonSchema $schema): bool
    {
        $validator = new \JsonSchema\Validator();

        $resolver = new \JsonSchema\SchemaStorage(new \JsonSchema\Uri\UriRetriever(), new \JsonSchema\Uri\UriResolver());
        $schema->resolve($resolver);

        return $schema->validate(\json_decode($json, false), $validator);
    }
}
