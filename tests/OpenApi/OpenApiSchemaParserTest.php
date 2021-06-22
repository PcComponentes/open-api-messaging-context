<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Tests\OpenApi;

use PcComponentes\OpenApiMessagingContext\OpenApi\OpenApiSchemaParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class OpenApiSchemaParserTest extends TestCase
{
    /** @test */
    public function given_valid_schema_when_parse_v3_response_then_ensure_parsed_schema_is_correct(): void
    {
        $allSpec = Yaml::parse(\file_get_contents(__DIR__ . '/valid-openapi-v3-spec.yaml'));

        $path = '/url/endpoint';
        $method = 'post';
        $contentType = 'application/json';
        $statusCode = 200;

        $schema = (new OpenApiSchemaParser($allSpec))->fromResponse($path, $method, $statusCode, $contentType);

        $expectedJson = '{"type":"object","properties":{"id":{"type":"integer"},"name":{"type":"string"}}}';
        $this->assertJsonStringEqualsJsonString($expectedJson, \json_encode($schema));
    }
}
