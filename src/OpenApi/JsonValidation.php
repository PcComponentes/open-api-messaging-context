<?php declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

final class JsonValidation
{
    private string $json;
    private ?string $errorMessage;

    public function __construct(string $json, ?string $errorMessage)
    {
        $this->json = $json;
        $this->errorMessage = $errorMessage;
    }

    public function hasError(): bool
    {
        return null !== $this->errorMessage;
    }

    public function json(): string
    {
        return $this->json;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
