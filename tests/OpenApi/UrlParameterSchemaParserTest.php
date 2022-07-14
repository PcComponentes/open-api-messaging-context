<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Tests\OpenApi;

use PcComponentes\OpenApiMessagingContext\OpenApi\UrlParameterSchemaParser;
use PHPUnit\Framework\TestCase;

final class UrlParameterSchemaParserTest extends TestCase
{
    /**
     * @test
     * @dataProvider cases
     */
    public function tests(array $spec, string $url, string $method, array $expectedSchema): void
    {
        $schemaParser = new UrlParameterSchemaParser($spec);
        $actualSchema = $schemaParser->parse($url, $method);

        $this->assertEquals(
            $expectedSchema,
            $actualSchema->schema(),
        );
    }

    public function cases(): iterable
    {
        yield 'validate schema with one parameter in path' => [
            [
                'paths' => [
                    '/url/resource/{resource_id}' => [
                        'get' => [
                            'parameters' => [
                                [
                                    'name' => 'resource_id',
                                    'in' => 'path',
                                    'description' => 'resource id',
                                    'schema' => [
                                        'type' => 'string',
                                        'required' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/url/resource/1',
            'get',
            [
                'type' => 'object',
                'properties' => [
                    'resource_id' => [
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
            ],
        ];

        yield 'validate schema with two parameters in path' => [
            [
                'paths' => [
                    '/url/one/{id_one}/two/{id_two}' => [
                        'put' => [
                            'parameters' => [
                                [
                                    'name' => 'id_one',
                                    'in' => 'path',
                                    'description' => 'first id',
                                    'schema' => [
                                        'type' => 'string',
                                        'required' => true,
                                    ],
                                ],
                                [
                                    'name' => 'id_two',
                                    'in' => 'path',
                                    'description' => 'second id',
                                    'schema' => [
                                        'type' => 'string',
                                        'required' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/url/one/1/two/2',
            'put',
            [
                'type' => 'object',
                'properties' => [
                    'id_one' => [
                        'type' => 'string',
                        'required' => true,
                    ],
                    'id_two' => [
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
            ],
        ];
    }
}
