<?php

namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\EntityContainerInterface;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\RelationMapperInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
// Symfony
use Symfony\Component\Console\Style\SymfonyStyle;
// PHP
use ArrayObject;
use SplFileInfo;

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

    public function addCreated(BaseEntityInterface $entity): void;
    public function hasCreated(BaseEntityInterface $entity): bool;
    public function clearCreateds(): bool;
    public function clearPersisteds(): bool;
    public function findCreated(string $euidOrUname): ?BaseEntityInterface;
    // public function cleanAndPrepareDataToDeserialize(array &$data, string $classname, ?string $uname = null): ?BaseEntityInterface;
    public static function getNormalizeGroups(string|BaseEntityInterface $class, ?string $type = null): array;
    public static function getDenormalizeGroups(string|BaseEntityInterface $class, ?string $type = null): array;
    // Normalize
    public function normalize(mixed $data, ?string $format = null, ?array $context = [], ?bool $convertToArrayList = false): array|string|int|float|bool|ArrayObject|null;
    public function denormalize(mixed $data, string $classname, ?string $format = null, ?array $context = []): mixed;
    // Normalize entity
    public function normalizeEntity(BaseEntityInterface $entity, ?string $format = null, ?array $context = []): array|string|int|float|bool|ArrayObject|null;
    public function denormalizeEntity(array|EntityContainerInterface $data, string $classname, ?string $format = null, ?array $context = []): BaseEntityInterface;
    // Serialize
    public function serialize(mixed $data, string $format, ?array $context = [], ?bool $convertToArrayList = false): string;
    public function deserialize(string $data, string $type, string $format, ?array $context = []): mixed;

    // Path
    public function setCurrentPath(?string $path = null): void;
    public function getCurrentPath(): SplFileInfo;

    public function tryFindCatalogueClassname(string $uname): ?string;
    // public function tryFindClassnameOfUname(string $uname, array $availableClassnames = [], ?string $defaultClassname = null): ?string;
    public function getYamlData(array $filenamesOrClassnames = [], int $mode_report = 0): array;
    public static function HumanizeEntitiesYamlData(array &$data): void;
    public static function UnhumanizeEntitiesYamlData(array &$data): void;
    public function getReport(array|string $filenamesOrClassnames = [], int $mode_report = 2): array;
    public function generateEntitiesFromClass(string $classname, bool $replace = false, ?SymfonyStyle $io = null, bool $flush = true): OpresultInterface;
    public function generateEntities($classname, array $items, bool $replace = false, ?SymfonyStyle $io = null, bool $flush = true): OpresultInterface;

    // Createds
    // public function getCreateds(): array;
    // public function addCreated(BaseEntityInterface $entity): void;
    // public function clearCreateds(): bool;
    // public function clearPersisteds(): bool;
    // public function findCreated(string $euidOrUname): ?BaseEntityInterface;

    public function getRelationMapper(string $classname): RelationMapperInterface;
    public function findEntityByEuid(string $euid): ?BaseEntityInterface;
    public function findEntityByUname(string $uname): ?BaseEntityInterface;
    public function getClassnameByUname(string $uname): ?string;
    public function getClassnameByEuidOrUname(string $euidOrUname): ?string;

}
