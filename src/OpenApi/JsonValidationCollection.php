<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

final class JsonValidationCollection
{
    private array $jsonValidations;

    public function __construct(JsonValidation ...$jsonValidations)
    {
        $this->jsonValidations = $jsonValidations;
    }

    public function hasAnyError()
    {
        foreach ($this->jsonValidations as $theJsonValidation) {
            if (true === $theJsonValidation->hasError()) {
                return true;
            }
        }

        return false;
    }

    public function buildErrorMessage(): string
    {
        if (false === $this->hasAnyError()) {
            return '';
        }

        $validationsWithErrors = \array_filter(
            $this->jsonValidations,
            static fn (JsonValidation $elem) => $elem->hasError()
        );

        $msg = \PHP_EOL;

        foreach ($validationsWithErrors as $index => $validation) {
            $msg .= \sprintf('JSON message %d does not validate. ' . \PHP_EOL, $index);
            $msg .= $validation->errorMessage();
        }

        return $msg;
    }
}
