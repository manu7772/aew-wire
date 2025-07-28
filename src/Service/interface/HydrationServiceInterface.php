<?php

namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\HydradataCollectionInterface;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataCollectionInterface;
// Symfony
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
// PHP
use ArrayObject;
use Doctrine\Common\Collections\Collection;

interface HydrationServiceInterface extends WireServiceInterface
{
    public const DEFAULT_DATA_PATH = 'src/DataBasics/data/';
    public const MAIN_GROUP = 'hydrate';
    public const DEEP_POPULATE_MODE = true;

    public function getSerializer(): SerializerInterface & NormalizerInterface & DenormalizerInterface;
    public function getObjectMapper(): ObjectMapperInterface;
    // Normalize
    public function normalize(mixed $data, ?string $format = null, ?array $context = [], ?bool $convertToArrayList = false): array|string|int|float|bool|ArrayObject|null;
    public function denormalize(mixed $data, string $classname, ?string $format = null, ?array $context = []): mixed;
    // Serialize
    public function serialize(mixed $data, string $format, ?array $context = [], ?bool $convertToArrayList = false): string;
    public function deserialize(string $data, string $type, string $format, ?array $context = []): mixed;
    // From data
    public function tryFindCreated(int|string|array $value, ?string $classname = null): ?object;
    public function getCreateds(): Collection;
    public function generate(int $index, array $items = [], bool $flush = false, ?string $path = null): OpresultInterface;
    public function getDataFiles(?string $path = null): array;
    public function getHydratableClasses(): WireClassMetadataCollectionInterface;
    public static function parseYamlData(string $yaml): array|false;
    // public function getHydrationData(string|array $interfaces, ?string $path = null): array;
    public function getHydatableData(?string $path = null): HydradataCollectionInterface;
    public function requestDataToIndexes(string $json): array;

}
