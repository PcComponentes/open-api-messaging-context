<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Tests\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Session;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Testwork\Environment\Environment;
use PcComponentes\OpenApiMessagingContext\Behat\RequestValidatorOpenApiContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;

final class RequestValidatorOpenApiContextTest extends TestCase
{
    /** @test */
    public function test(): void
    {
        $requestValidatorOpenApiContext = new RequestValidatorOpenApiContext(
            __DIR__,
        );

        $this->mockClient($requestValidatorOpenApiContext);

        $requestBody = [
            'attributes' => [
                [
                    'key' => 'foo',
                    'value' => 'bar'
                ],
            ]
        ];

        $pyStringNode = new PyStringNode(
            [\json_encode($requestBody)],
            '',
        );

        $method = 'put';
        $url = '/url/resource/10';
        $openApiPath = '/../OpenApi/valid-openapi-v3-spec.yaml';

        $requestValidatorOpenApiContext->theJsonRequestShouldBeValidAccordingToOpenApiSchema(
            $method,
            $url,
            $openApiPath,
            $pyStringNode,
        );
    }

    private function mockClient(RequestValidatorOpenApiContext $requestValidatorOpenApiContext): void
    {

        $clientMock = $this->createMock(AbstractBrowser::class);
        $clientMock
            ->expects($this->once())
            ->method('request');

        $driverMock = $this->createMock(BrowserKitDriver::class);
        $driverMock
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($clientMock)
        ;

        $sessionMock = $this->createMock(Session::class);
        $sessionMock
            ->expects($this->once())
            ->method('getDriver')
            ->willReturn($driverMock)
        ;

        $minkContextMock = $this->createMock(MinkContext::class);
        $minkContextMock
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($sessionMock)
        ;

        $contextMock = $this->getMockBuilder(Environment::class)
            ->addMethods(['getContext'])
            ->onlyMethods(['getSuite', 'bindCallee'])
            ->getMock()
        ;
        $contextMock
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($minkContextMock)
        ;

        $beforeScenarioScope = new BeforeScenarioScope(
            $contextMock,
            $this->createMock(FeatureNode::class),
            $this->createMock(ScenarioInterface::class),
        );

        $requestValidatorOpenApiContext->bootstrapEnvironment($beforeScenarioScope);
    }
}
