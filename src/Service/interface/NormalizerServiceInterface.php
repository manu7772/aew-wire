<?php

namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\EntityContainerInterface;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\RelationMapperInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
// Symfony
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
// PHP
use ArrayObject;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use SplFileInfo;

interface NormalizerServiceInterface extends WireServiceInterface
{
    public const DEFAULT_DATA_PATH = 'src/DataBasics/data/';
    public const MAIN_GROUP = 'hydrate';
    /**
     * If you have a nested structure, child objects will be overwritten with new instances unless you set DEEP_OBJECT_TO_POPULATE to true.
     * Si vous avez une structure imbriquée, les objets enfants seront écrasés par de nouvelles instances, sauf si vous définissez DEEP_OBJECT_TO_POPULATE sur true.
     */
    public const DEEP_POPULATE_MODE = true;
    public const NORMALIZATION_GROUPS = [
        '_universal' => [
            'normalize' => ['identifier', '__type__'],
            'denormalize' => ['__type__'],
        ],
        '_default' => [
            'normalize' => ['identifier', '__shortname__.__type__', '__type__'],
            'denormalize' => ['__shortname__.__type__', '__type__'],
        ],
        'debug' => [
            'normalize' => ['identifier', '__shortname__.__type__', '__type__'],
            'denormalize' => ['__shortname__.__type__', '__type__'],
        ],
    ];
    public const AVAILABLE_MODES = [
        0 => 'Raw data',
        1 => 'Raw data + extra',
        2 => 'Compiled data',
        3 => 'EntityContainers',
    ];

    public function __construct(AppWireServiceInterface $appWire, WireEntityManagerInterface $wireEm, SerializerInterface $serializer, LoggerInterface $logger);

    public function getSerializer(): SerializerInterface & NormalizerInterface & DenormalizerInterface;
    // Createds
    public function addCreated(BaseEntityInterface $entity): void;
    public function getCreateds(): Collection;
    public function hasCreated(BaseEntityInterface $entity): bool;
    public function clearCreateds(): bool;
    public function clearPersisteds(): bool;
    public function findCreated(string $euidOrUname): ?BaseEntityInterface;
    public function findUnameCreated(string $euidOrUname): ?UnameInterface;
    // public function cleanAndPrepareDataToDeserialize(array &$data, string $classname, ?string $uname = null): ?BaseEntityInterface;
    public static function getNormalizeGroups(null|string|BaseEntityInterface $class, ?string $type = null): array;
    public static function getDenormalizeGroups(null|string|BaseEntityInterface $class, ?string $type = null): array;
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

    public function getRelationMapper(string $classname): RelationMapperInterface;
    public function findEntityByEuid(string $euid): ?BaseEntityInterface;
    public function findEntityByUname(string $uname): ?BaseEntityInterface;
    public function findUnameByUname(string $uname): ?UnameInterface;
    public function getClassnameByUname(string $uname): ?string;
    public function getClassnameByEuidOrUname(string $euidOrUname): ?string;

}
