Little context in behat for validate published messages and http responses according to an AsyncApi and OpenApi specification.

# Installation
```
composer require --dev pccomponentes/open-api-messaging-context
```

# Configuration

This package uses [Friends Of Behat Symfony Extension](https://github.com/FriendsOfBehat/SymfonyExtension), you must see how to configure your behat with this extension.

# How to use

Define SpyMiddleware as a service in your test environment:

```yaml
services:
  spy.message.middleware:
    class: PcComponentes\OpenApiMessagingContext\Messaging\SpyMiddleware
```

Add SpyMiddleware as a middleware in `messenger.yaml` configuration in `test` environment.

```yaml
framework:
  messenger:
    buses:
      messenger.bus.event-broker:
        middleware:
          - spy.message.middleware
      messenger.bus.command:
        middleware:
          - spy.message.middleware
```

Configure the context in your `behat.yml`

```yaml
default:
  suites:
    default:
      contexts:
        - PcComponentes\OpenApiMessagingContext\Behat\MessageValidatorOpenApiContext:
          - '%%kernel.project_dir%%'
          - '@spy.message.middleware'
```

And use the `Then` statement for validate messages:

```gherkin
  Scenario: My awesome scenario
    Given the environment is clean
    When I send a "POST" request to "/resource/" with body:
    """
{
  "my-awesome-data": "foo",
}
    """
    Then the published message "pccomponentes.example.1.domain_event.resource.resource_created" should be valid according to swagger "docs/asyncapi.yml"
```

Your schema must be writen according to https://www.asyncapi.com/ specification.

Available for version schemas `1.2.0` and `2.0.0`.

# Available contexts

## MessageValidatorOpenApiContext
Check the content of the dispatched message that matches with your asyncapi file:
```gherkin
Then the published message "pccomponentes.example.1.domain_event.resource.resource_created" should be valid according to swagger "docs/asyncapi.yml"
```
Check if message has dispatched:
```gherkin
Then the message "pccomponentes.example.1.domain_event.resource.resource_created" should be dispatched
```
Configuration:
```yaml
- PcComponentes\OpenApiMessagingContext\Behat\MessageValidatorOpenApiContext:
  - '%%kernel.project_dir%%'
  - '@spy.message.middleware'
```

## ResponseValidatorOpenApiContext
Check if http responses are documented in your openapi file:
```gherkin
Then the response should be valid according to OpenApi "docs/openapi.yml" with path "/your/openapi/path/"
```
Configuration:
```yaml
- PcComponentes\OpenApiMessagingContext\Behat\ResponseValidatorOpenApiContext:
  - '%%kernel.project_dir%%'
```

## SimpleMessageContext
`When` step for SimpleMessage input:
```gherkin
When I receive a simple message with payload:
"""
{
  "data": {
    "message_id": "d2439fd8-be54-4233-ba59-fe3187620505",
    "type": "pccomponentes.example.1.command.resource.create_resource",
    "attributes": {
      "id": "3c44e76e-1369-4a95-84ac-3a78f9c1f354",
      "my_awesome_data": "foo",
    }
  }
}
"""
Then the message "pccomponentes.example.1.domain_event.resource.resource_created" should be dispatched
```
This is useful to combine it with `Then` step in `MessageValidatorOpenApiContext`

Configuration:
```yaml
- PcComponentes\OpenApiMessagingContext\Behat\SimpleMessageContext:
  - '@messenger.bus.command' ##Your command bus with spy.message.middleware
  - '@your.simple_message_deserializer.service'
```
*TIP* If you are using [pccomponentes/messenger-bundle](https://github.com/PcComponentes/messenger-bundle) you can use `@pccom.messenger_bundle.simple_message.serializer.stream_deserializer` for deserializer service

## AggregateMessageContext
`When` step for AggregateMessage input:
```gherkin
When I receive an aggregate message with payload:
"""
{
  "data": {
    "message_id": "2b8c7e00-219e-4d12-8b0e-dac2cc432410",
    "type": "pccomponentes.example.1.domain_event.resource.resource_created",
    "occurred_on": "1554900327",
    "attributes": {
      "aggregate_id": "0e7c57f8-d679-4605-ba27-3008b9636a0a",
      "status": "OPEN"
    }
  }
}
"""
Then the message "pccomponentes.example.1.command.resource.create_resource_projection" should be dispatched
```
This is useful to combine it with `Then` step in `MessageValidatorOpenApiContext`

Configuration:
```yaml
- PcComponentes\OpenApiMessagingContext\Behat\AggregateMessageContext:
  - '@messenger.bus.events' ## Your event bus with spy.message.middleware
  - '@your.aggregate_message_deserializer.service'
```
*TIP* If you are using [pccomponentes/messenger-bundle](https://github.com/PcComponentes/messenger-bundle) you can use `@pccom.messenger_bundle.aggregate_message.serializer.stream_deserializer` for deserializer service
