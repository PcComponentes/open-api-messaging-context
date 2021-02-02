<?php

namespace PcComponentes\OpenApiMessagingContext\Tests\AsyncApi;

use PcComponentes\OpenApiMessagingContext\AsyncApi\AsyncApiParser;
use PcComponentes\OpenApiMessagingContext\Tests\Messaging\DomainEventFake;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class AsyncApiParserTest extends TestCase
{
    /** @test */
    public function given_valid_schema_when_parse_v12_then_get_parsed_schema(): void
    {
        $allSpec = Yaml::parse(file_get_contents(__DIR__ . '/valid-asyncapi-v12-spec.yaml'));
        $schema = (new AsyncApiParser($allSpec))->parse(DomainEventFake::messageName());
        $jsonCompleted = '{"type":"object","required":["message_id","type"],"properties":{"message_id":{"type":"string"},"type":{"type":"string"},"attributes":{"type":"object","required":["some_attribute"],"properties":{"some_attribute":{"type":"string"}}}}}';
        $this->assertJsonStringEqualsJsonString(\json_encode($schema), $jsonCompleted);
    }

    /** @test */
    public function given_valid_v12_schema_when_parse_non_existent_topic_then_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Topic with name <non.existent.topic> not found');
        $allSpec = Yaml::parse(file_get_contents(__DIR__ . '/valid-asyncapi-v12-spec.yaml'));
        (new AsyncApiParser($allSpec))->parse('non.existent.topic');
    }

    /** @test */
    public function given_valid_schema_when_parse_v20_then_get_parsed_schema(): void
    {
        $allSpec = Yaml::parse(file_get_contents(__DIR__ . '/valid-asyncapi-v20-spec.yaml'));
        $schema = (new AsyncApiParser($allSpec))->parse(DomainEventFake::messageName());
        $jsonCompleted = '{"type":"object","required":["message_id","type"],"properties":{"message_id":{"type":"string"},"type":{"type":"string"},"attributes":{"type":"object","required":["some_attribute"],"properties":{"some_attribute":{"type":"string"}}}}}';
        $this->assertJsonStringEqualsJsonString(\json_encode($schema), $jsonCompleted);
    }

    /** @test */
    public function given_valid_v20_schema_when_parse_non_existent_topic_then_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Topic with name <non.existent.topic> not found');
        $allSpec = Yaml::parse(file_get_contents(__DIR__ . '/valid-asyncapi-v20-spec.yaml'));
        (new AsyncApiParser($allSpec))->parse('non.existent.topic');
    }
}
