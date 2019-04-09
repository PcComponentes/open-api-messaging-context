Little context in behat for validate published messages according to an OpenApi specification.

# How to use

Define SpyMiddleware as a service in your test environment:

```yaml
Pccomponentes\OpenApiMessagingContext\Messaging\SpyMiddleware:
    class: Pccomponentes\OpenApiMessagingContext\Messaging\SpyMiddleware
    arguments:
      - '@your_message_serializer'
```

See [pccomponentes/amqp](https://github.com/PcComponentes/amqp) library for more information about message serializer.

Add SpyMiddleware as a middleware in messenger.yaml configuration in test environment.

```yaml
framework:
  messenger:
    buses:
      messenger.bus.event-broker:
        middleware:
          - 'Pccomponentes\OpenApiMessagingContext\Messaging\SpyMiddleware'
```

Configure the context in your `behat.yml`

```yaml
default:
  suites:
    default:
      contexts:
        - Pccomponentes\OpenApiMessagingContext\Behat\MessageValidatorOpenApiContext:
          - '%%kernel.project_dir%%'
          - '@Pccomponentes\OpenApiMessagingContext\Messaging\SpyMiddleware'
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
    Then the published message "pccomponentes.example.1.domain_event.resource.resource_created" should be valid according to swagger "docs/openapi/offer-messages.yml"
```

Your schema must be writen according to https://www.asyncapi.com/ specification.
See an [example](tests/OpenApi/valid-spec.yaml).