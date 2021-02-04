<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;

final class JsonSchema
{
    private $schema;

    public function __construct($schema)
    {
        $this->schema = $schema;
    }

    public function validate(string $json, Validator $validator): JsonValidation
    {
        $validator->check(\json_decode($json), $this->schema);

        $msg = null;

        if (!$validator->isValid()) {
            $msg = 'Violations:'.\PHP_EOL;
            foreach ($validator->getErrors() as $error) {
                $msg .= sprintf("  - [%s] %s".\PHP_EOL, $error['property'], $error['message']);
            }
        }

        return new JsonValidation($json, $msg);
    }
}
