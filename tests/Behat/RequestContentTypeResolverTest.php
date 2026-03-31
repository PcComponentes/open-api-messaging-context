<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Tests\Behat;

use PcComponentes\OpenApiMessagingContext\Behat\ResponseValidatorOpenApiContext\RequestContentTypeResolver;
use PHPUnit\Framework\TestCase;

final class RequestContentTypeResolverTest extends TestCase
{
    /** @test */
    public function testResolveReturnsMimeTypeWhenRequestExposesContentTypeFormat(): void
    {
        $resolver = new RequestContentTypeResolver();
        $request = new class () {
            public function getContentTypeFormat(): string
            {
                return 'json';
            }

            public function getMimeType(string $format): string
            {
                return 'application/' . $format;
            }
        };

        self::assertSame('application/json', $resolver->resolve($request));
    }

    /** @test */
    public function testResolveReturnsMimeTypeWhenRequestExposesLegacyContentType(): void
    {
        $resolver = new RequestContentTypeResolver();
        $request = new class () {
            public function getContentType(): string
            {
                return 'json';
            }

            public function getMimeType(string $format): string
            {
                return 'application/' . $format;
            }
        };

        self::assertSame('application/json', $resolver->resolve($request));
    }

    /** @test */
    public function testResolveReturnsNullWhenRequestDoesNotExposeSupportedContentTypeMethods(): void
    {
        $resolver = new RequestContentTypeResolver();
        $request = new class () {
            public function getMimeType(string $format): string
            {
                return 'application/' . $format;
            }
        };

        self::assertNull($resolver->resolve($request));
    }
}
