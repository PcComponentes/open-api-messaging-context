<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat;

use Behat\Behat\Context\Context;
use PcComponentes\OpenApiMessagingContext\AsyncApi\AsyncApiParser;
use PcComponentes\OpenApiMessagingContext\Messaging\SpyMiddleware;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonSchema;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidationCollection;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidationException;
use PcComponentes\OpenApiMessagingContext\OpenApi\JsonValidator;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class MessageValidatorOpenApiContext extends ValidatorApiContext implements Context
{
    public function __construct(
        private string $rootPath,
        private SpyMiddleware $spyMiddleware,
        private CacheInterface $cacheAdapter,
    ) {
    }

    /** @BeforeScenario */
    public function bootstrapEnvironment(): void
    {
        $this->spyMiddleware->reset();
    }

    /** @Then the published message :name should be valid according to swagger :dumpPath */
    public function theMessageShouldBeValidAccordingToTheSwagger($name, $dumpPath): void
    {
        $path = \realpath($this->rootPath . '/' . $dumpPath);
        $this->checkSchemaFile($path);

        $jsonMessages = $this->spyMiddleware->getMessagesFromName($name);

        $allSpec = $this->cacheAdapter->get(
            \md5($path),
            function (ItemInterface $item) use ($path) {
                $item->expiresAfter(null);

                $allSpec = Yaml::parse(file_get_contents($path));

                return $this->getDataExternalReferences($allSpec, $path);
            },
        );

        $schema = (new AsyncApiParser($allSpec))->parse($name);

        $validations = [];

        foreach ($jsonMessages as $theJsonMessage) {
            $validator = new JsonValidator($theJsonMessage, new JsonSchema(\json_decode(\json_encode($schema), false)));
            $validations[] = $validator->validate();
        }

        $jsonValidation = new JsonValidationCollection(...$validations);

        if ($jsonValidation->hasAnyError()) {
            throw new JsonValidationException($jsonValidation->buildErrorMessage());
        }
    }

    /** @Then the message :name should be dispatched */
    public function theMessageShouldBeDispatched(string $name): void
    {
        if (false === $this->spyMiddleware->hasMessage($name)) {
            throw new \Exception(
                \sprintf('Message %s was expected to dispatch, actually not dispatched', $name),
            );
        }
    }

    /** @Then the message :name should be dispatched :times times */
    public function theMessageShouldBeDispatchedManyTimes(string $name, int $times): void
    {
        $countMessages = $this->spyMiddleware->countMessagesFromName($name);

        if ($times !== $countMessages) {
            throw new \Exception(
                \sprintf(
                    'Message %s was expected to dispatch %d times, actually dispatched %d times.',
                    $name,
                    $times,
                    $countMessages,
                ),
            );
        }
    }

    /** @Then the message :name should not be dispatched */
    public function theMessageShouldNotBeDispatched(string $name): void
    {
        if (true === $this->spyMiddleware->hasMessage($name)) {
            throw new \Exception(
                \sprintf('Message %s was not expected to be dispatched', $name),
            );
        }
    }
}
