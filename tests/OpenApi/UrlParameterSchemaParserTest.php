<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Tests\OpenApi;

use PcComponentes\OpenApiMessagingContext\OpenApi\UrlParameterSchemaParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class UrlParameterSchemaParserTest extends TestCase
{
    /** @test */
    public function given_valid_schema_when_parse_v3_get_request_then_ensure_is_parsed(): void
    {
        $path = '/url/endpoint/1';
        $method = 'get';

        $schemaParser = new UrlParameterSchemaParser($this->allSpec());
        $actualSchema = $schemaParser->parse($path, $method);

        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'required' => true,
                ],
            ],
        ];

        $this->assertEquals(
            $expectedSchema,
            $actualSchema->schema(),
        );
    }

    /** @test */
    public function given_valid_schema_when_parse_v3_multiple_url_parameters_then_ensure_is_parsed(): void
    {
        $path = '/url/endpoint/1/resource/10';
        $method = 'post';

        $schemaParser = new UrlParameterSchemaParser($this->allSpec());
        $actualSchema = $schemaParser->parse($path, $method);

        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'required' => true,
                ],
                'resource_id' => [
                    'type' => 'string',
                    'required' => true,
                ],
            ],
        ];

        $this->assertEquals(
            $expectedSchema,
            $actualSchema->schema(),
        );
    }

    private function allSpec(): array
    {
        return Yaml::parse(\file_get_contents(__DIR__ . '/valid-openapi-v3-spec.yaml'));
    }
}
