<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat;

use Symfony\Component\Yaml\Yaml;

abstract class ValidatorApiContext
{
    protected function checkSchemaFile($filename): void
    {
        if (false === \is_file($filename)) {
            throw new \RuntimeException(
                'The JSON schema doesn\'t exist',
            );
        }
    }

    protected function getDataExternalReferences(array $allSpec, string $originalPath): array
    {
        $externalReferences = $this->externalReferencesExtractor($allSpec);

        $dataExternalReferences = [];

        foreach ($externalReferences as $externalReference) {
            [$pathExternalReference] = \explode('#', $externalReference);
            $newPath = \realpath(\dirname($originalPath) . '/' . $pathExternalReference);

            $this->checkSchemaFile($newPath);
            $data = Yaml::parse(\file_get_contents($newPath));
            $dataExternalReferences[$pathExternalReference] = $data;
        }

        return \array_merge($allSpec, $dataExternalReferences);
    }

    private function externalReferencesExtractor(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                $result = \array_merge($result, $this->externalReferencesExtractor($value));
            }

            if ('$ref' !== $key) {
                continue;
            }

            if (false === \str_contains($value, '.yaml')) {
                continue;
            }

            $result[] = $value;
        }

        return $result;
    }
}
