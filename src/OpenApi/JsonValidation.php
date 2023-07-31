<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

final class JsonValidation
{
    public function __construct(private string $json, private ?string $errorMessage)
    {
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
