<?php declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

use JsonSchema\Validator;

final class JsonValidator
{
    private string $jsonToValidate;
    private JsonSchema $jsonSchema;

    public function __construct(string $jsonToValidate, JsonSchema $jsonSchema)
    {
        $this->jsonToValidate = $jsonToValidate;
        $this->jsonSchema = $jsonSchema;
    }

    public function validate(): JsonValidation
    {
        return $this->jsonSchema->validate($this->jsonToValidate, new Validator());
    }
}
