<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\OpenApi;

use JsonSchema\Validator;

final class JsonSchema
{
    public function __construct(private $schema)
    {
    }

    public function validate(string $json, Validator $validator): JsonValidation
    {
        $validator->check(\json_decode($json), $this->schema);

        $msg = null;

        if (!$validator->isValid()) {
            $msg = 'Violations:'.\PHP_EOL;

            foreach ($validator->getErrors() as $error) {
                $msg .= \sprintf("  - [%s] %s".\PHP_EOL, $error['property'], $error['message']);
            }
        }

        return new JsonValidation($json, $msg);
    }
}
