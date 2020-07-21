<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat;

use Behat\Behat\Context\Context;
use PcComponentes\OpenApiMessagingContext\AsyncApi\AsyncApiParser;
use PcComponentes\OpenApiMessagingContext\Messaging\SpyMiddleware;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonSchema;
use Symfony\Component\Yaml\Yaml;

final class MessageValidatorOpenApiContext implements Context
{
    private string $rootPath;
    private SpyMiddleware $spyMiddleware;

    public function __construct(string $rootPath, SpyMiddleware $spyMiddleware)
    {
        $this->rootPath = $rootPath;
        $this->spyMiddleware = $spyMiddleware;
    }

    /**
     * @BeforeScenario
     */
    public function bootstrapEnvironment(): void
    {
        $this->spyMiddleware->reset();
    }

    /**
     * @Then the published message :name should be valid according to swagger :dumpPath
     */
    public function theMessageShouldBeValidAccordingToTheSwagger($name, $dumpPath): void
    {
        $path = realpath($this->rootPath . '/' . $dumpPath);
        $this->checkSchemaFile($path);

        $eventJson = $this->spyMiddleware->getMessage($name);

        $allSpec = Yaml::parse(file_get_contents($path));
        $schema = (new AsyncApiParser($allSpec))->parse($name);

        $this->validate($eventJson, new JsonSchema(\json_decode(\json_encode($schema), false)));
    }

    /**
     * @Then the message :name should be dispatched
     */
    public function theMessageShouldBeDispatched(string $name): void
    {
        if (false === $this->spyMiddleware->hasMessage($name)) {
            throw new \Exception(sprintf('Message %s not dispatched', $name));
        }
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
