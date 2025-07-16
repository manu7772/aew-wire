<?php

namespace Aequation\WireBundle\Service\interface;

// Symfony

use Aequation\WireBundle\Component\interface\HydradataCollectionInterface;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
// PHP
use ArrayObject;

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
    public function generate(int $index, array $items = [], bool $persist = false, ?string $path = null): OpresultInterface;
    public function getDataFiles(?string $path = null): array;
    public static function parseYamlData(string $yaml): array|false;
    // public function getHydrationData(string|array $interfaces, ?string $path = null): array;
    public function getHydatableData(?string $path = null): HydradataCollectionInterface;

}
