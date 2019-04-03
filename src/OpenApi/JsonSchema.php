<?php

namespace Pccomponentes\OpenApiMessagingContext\OpenApi;

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;

final class JsonSchema
{
    private $schema;
    private $uri;

    public function __construct(\stdClass $schema, string $uri = null)
    {
        $this->schema = $schema;
        $this->uri = $uri;
    }

    public function resolve(SchemaStorage $resolver)
    {
        if (!$this->hasUri()) {
            return $this;
        }

        $this->schema = $resolver->resolveRef($this->uri);

        return $this;
    }

    public function validate(\stdClass $json, Validator $validator)
    {
        $validator->check($json, $this->schema);

        if (!$validator->isValid()) {
            $msg = "JSON does not validate. Violations:".PHP_EOL;
            foreach ($validator->getErrors() as $error) {
                $msg .= sprintf("  - [%s] %s".PHP_EOL, $error['property'], $error['message']);
            }
            throw new \Exception($msg);
        }

        return true;
    }

    public function schema(): string
    {
        return $this->schema;
    }

    private function hasUri()
    {
        return null !== $this->uri;
    }
}
