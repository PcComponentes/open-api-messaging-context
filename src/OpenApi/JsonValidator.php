<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

use JsonSchema\Validator;

final class JsonValidator
{
    public function __construct(private string $jsonToValidate, private JsonSchema $jsonSchema)
    {
    }

    public function validate(): JsonValidation
    {
        return $this->jsonSchema->validate($this->jsonToValidate, new Validator());
    }
}
