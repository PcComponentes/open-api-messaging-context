<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat\ResponseValidatorOpenApiContext;

final class RequestContentTypeResolver
{
    public function resolve(object $request): ?string
    {
        $contentTypeFormat = $this->contentTypeFormat($request);

        if (null === $contentTypeFormat || false === \method_exists($request, 'getMimeType')) {
            return null;
        }

        return $request->getMimeType($contentTypeFormat);
    }

    private function contentTypeFormat(object $request): ?string
    {
        if (\method_exists($request, 'getContentTypeFormat')) {
            return $request->getContentTypeFormat();
        }

        if (\method_exists($request, 'getContentType')) {
            return $request->getContentType();
        }

        return null;
    }
}
