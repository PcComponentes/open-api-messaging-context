<?php

namespace Pccomponentes\OpenApiMessagingContext\Behat;

use Behat\Behat\Context\Context;
use Pccomponentes\OpenApiMessagingContext\Messaging\SpyMiddleware;
use Pccomponentes\OpenApiMessagingContext\OpenApi\JsonSchema;
use Pccomponentes\OpenApiMessagingContext\OpenApi\OpenApiParser;
use Symfony\Component\Yaml\Yaml;

final class MessageValidatorOpenApiContext implements Context
{
    private $spyMiddleware;

    public function __construct(SpyMiddleware $spyMiddleware)
    {
        $this->spyMiddleware = $spyMiddleware;
    }

    /**
     * @BeforeScenario
     */
    public function bootstrapEnvironment()
    {
        $this->spyMiddleware->reset();
    }

    /**
     * Checks, that response JSON matches with a swagger dump
     *
     * @Then the published message :name should be valid according to swagger :dumpPath
     */
    public function theJsonShouldBeValidAccordingToTheSwaggerSchema($name, $dumpPath)
    {
        $path = realpath(__DIR__ . '/../../../../' . $dumpPath);
        $this->checkSchemaFile($path);

        $eventJson = $this->spyMiddleware->getMessage($name);

        $allSpec = Yaml::parse(file_get_contents($path));
        $schema = (new OpenApiParser($allSpec))->parse($name);

        $this->validate($eventJson, new JsonSchema(json_decode(json_encode($schema))));
    }

    private function checkSchemaFile($filename)
    {
        if (false === is_file($filename)) {
            throw new \RuntimeException(
                'The JSON schema doesn\'t exist'
            );
        }
    }

    private function validate(string $json, JsonSchema $schema)
    {
        $validator = new \JsonSchema\Validator();

        $resolver = new \JsonSchema\SchemaStorage(new \JsonSchema\Uri\UriRetriever, new \JsonSchema\Uri\UriResolver);
        $schema->resolve($resolver);

        return $schema->validate(\json_decode($json), $validator);
    }
}
