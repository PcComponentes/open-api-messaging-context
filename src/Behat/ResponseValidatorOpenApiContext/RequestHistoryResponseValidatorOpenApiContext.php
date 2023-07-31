<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat\ResponseValidatorOpenApiContext;

use PcComponentes\OpenApiMessagingContext\Behat\ResponseValidatorOpenApiContext;
use PcComponentes\OpenApiMessagingContext\Utils\RequestHistory;
use Symfony\Contracts\Cache\CacheInterface;

final class RequestHistoryResponseValidatorOpenApiContext extends ResponseValidatorOpenApiContext
{
    private const CONTENT_TYPE_RESPONSE_HEADER_KEY = 'content-type';

    public function __construct(string $rootPath, private RequestHistory $requestHistory, CacheInterface $cacheAdapter)
    {
        parent::__construct($rootPath, $cacheAdapter);
    }

    protected function extractMethod(): string
    {
        return $this->requestHistory->getLastRequest()->getMethod();
    }

    protected function extractContentType(): ?string
    {
        return $this->requestHistory->getLastResponse()->headers->get(self::CONTENT_TYPE_RESPONSE_HEADER_KEY);
    }

    protected function extractStatusCode(): int
    {
        return $this->requestHistory->getLastResponse()->getStatusCode();
    }

    protected function extractContent(): string
    {
        return $this->requestHistory->getLastResponse()->getContent();
    }
}
