<?php
declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Behat;

use Symfony\Component\Yaml\Yaml;

abstract class ValidatorApiContext
{
    private array $dataExternalReferences = [];

    protected function checkSchemaFile(string $filename): void
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
            $dataExternalReferences[$pathExternalReference] = $this->getDataExternalReferencesCached($newPath);
        }

        return \array_merge($allSpec, $dataExternalReferences);
    }

    private function getDataExternalReferencesCached(string $newPath): array
    {
        if (false === \array_key_exists($newPath,  $this->dataExternalReferences)) {
            $this->checkSchemaFile($newPath);
            $data = Yaml::parse(\file_get_contents($newPath));
            $this->dataExternalReferences[$newPath] = $this->getDataExternalReferences($data, $newPath);
        }

        return $this->dataExternalReferences[$newPath];
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
