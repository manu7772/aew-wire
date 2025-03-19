<?php

namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\NormalizeDataContainer;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
// Symfony
use Symfony\Component\Console\Style\SymfonyStyle;
// PHP
use ArrayObject;

interface NormalizerServiceInterface extends WireServiceInterface
{
    public const DEFAULT_DATA_PATH = 'src/DataBasics/data/';
    public const MAIN_GROUP = 'hydrate';
    public const NORMALIZATION_GROUPS = [
        '_default' => [
            'normalize' => ['identifier', '__shortname__.__type__', '__type__'],
            'denormalize' => ['__shortname__.__type__', '__type__'],
        ],
        'debug' => [
            'normalize' => ['identifier', '__shortname__.__type__', '__type__'],
            'denormalize' => ['__shortname__.__type__', '__type__'],
        ],
    ];

    // public function cleanAndPrepareDataToDeserialize(array &$data, string $classname, ?string $uname = null): ?WireEntityInterface;
    public static function getNormalizeGroups(string|WireEntityInterface $class, ?string $type = null): array;
    public static function getDenormalizeGroups(string|WireEntityInterface $class, ?string $type = null): array;
    // Normalize
    public function normalize(mixed $data, ?string $format = null, ?array $context = [], ?bool $convertToArrayList = false): array|string|int|float|bool|ArrayObject|null;
    public function denormalize(mixed $data, string $classname, ?string $format = null, ?array $context = []): mixed;
    // Normalize entity
    public function normalizeEntity(WireEntityInterface $entity, ?string $format = null, ?array $context = []): array|string|int|float|bool|ArrayObject|null;
    public function denormalizeEntity(array|NormalizeDataContainer $data, string $classname, ?string $format = null, ?array $context = []): WireEntityInterface;
    // Serialize
    public function serialize(mixed $data, string $format, ?array $context = [], ?bool $convertToArrayList = false): string;
    public function deserialize(string $data, string $type, string $format, ?array $context = []): mixed;

    public function findPathYamlFiles(string $path): array|false;
    public function getPathYamlData(string $path): array|false;
    public function getYamlData(string $file): array|false;
    public function generateEntitiesFromClass(string $classname, bool $replace = false, ?SymfonyStyle $io = null): OpresultInterface;
    public function generateEntitiesFromFile(string $filename, bool $replace = false, ?SymfonyStyle $io = null): OpresultInterface;
    public function generateEntities($classname, array $items, bool $replace = false, ?SymfonyStyle $io = null): OpresultInterface;
}
