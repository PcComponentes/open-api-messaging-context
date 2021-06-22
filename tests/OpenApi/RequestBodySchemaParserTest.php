<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Tests\OpenApi;

use PcComponentes\OpenApiMessagingContext\OpenApi\RequestBodySchemaParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class RequestBodySchemaParserTest extends TestCase
{
    /** @test */
    public function given_valid_schema_when_parse_v3_from_post_request_then_ensure_is_parsed(): void
    {
        $path = '/url/endpoint';
        $method = 'post';
        $contentType = 'application/json';

        $schemaParser = new RequestBodySchemaParser($this->allSpec());
        $actualSchema = $schemaParser->parse($path, $method, $contentType);

        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                ],
                'name' => [
                    'type' => 'string',
                    'required' => false,
                ],
            ]
        ];

        $this->assertEquals(
            $expectedSchema,
            $actualSchema,
        );

    }

    private function allSpec(): array
    {
        return Yaml::parse(\file_get_contents(__DIR__ . '/valid-openapi-v3-spec.yaml'));
    }
}
