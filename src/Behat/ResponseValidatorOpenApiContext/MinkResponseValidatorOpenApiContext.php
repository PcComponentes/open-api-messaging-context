<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat\ResponseValidatorOpenApiContext;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use PcComponentes\OpenApiMessagingContext\Behat\ResponseValidatorOpenApiContext;

final class MinkResponseValidatorOpenApiContext extends ResponseValidatorOpenApiContext
{
    private const CONTENT_TYPE_RESPONSE_HEADER_KEY = 'content-type';

    private MinkContext $minkContext;

    /**
     * @BeforeScenario
     */
    public function bootstrapEnvironment(BeforeScenarioScope $scope): void
    {
        $this->minkContext = $scope->getEnvironment()->getContext(MinkContext::class);
    }

    protected function extractMethod(): string
    {
        $requestClient = $this->minkContext->getSession()->getDriver()->getClient();

        return $requestClient->getHistory()->current()->getMethod();
    }

    protected function extractContentType(): ?string
    {
        return $this->minkContext->getSession()->getResponseHeader(self::CONTENT_TYPE_RESPONSE_HEADER_KEY);
    }

    protected function extractStatusCode(): int
    {
        return $this->minkContext->getSession()->getStatusCode();
    }

    protected function extractContent(): string
    {
        return $this->minkContext->getSession()->getPage()->getContent();
    }
}
