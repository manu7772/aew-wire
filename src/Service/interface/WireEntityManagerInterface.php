<?php

namespace Aequation\WireBundle\Service\interface;

// Aequation

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
// PHP
use Closure;

interface WireEntityManagerInterface extends WireServiceInterface
{

    // Debug mode
    public function isDebugMode(): bool;
    public function incDebugMode(): bool;
    public function decDebugMode(): bool;
    public function resetDebugMode(): bool;

    public function getNormaliserService(): NormalizerServiceInterface;
    public function getAppWireService(): AppWireServiceInterface;
    public function getEntityService(string|WireEntityInterface $entity): ?WireEntityServiceInterface;
    public function getClassMetadata(null|string|WireEntityInterface $objectOrClass = null): ?ClassMetadata;
    public function getRepository(string|WireEntityInterface $objectOrClass): ?EntityRepository;
    public static function isAppWireEntity(string|object $objectOrClass): bool;
    public function getEntityNames(bool $asShortnames = false, bool $allnamespaces = false, bool $onlyInstantiables = false): array;
    public function getFinalEntities(bool $asShortnames = false, bool $allnamespaces = false): array;
    public function getEntityClassesOfInterface(string|array $interfaces, bool $allnamespaces = false, bool $onlyInstantiables = false): array;
    public function entityExists(string $classname, bool $allnamespaces = false, bool $onlyInstantiables = false): bool;
    public static function getConstraintUniqueFields(string $classname, bool|null $flatlisted = false): array;
    public function getRelateds(string|WireEntityInterface $objectOrClass, ?Closure $filter = null, bool $excludeSelf = false): array;
    public function getEntityManager(): EntityManagerInterface;
    public function getEm(): EntityManagerInterface;
    public function getUnitOfWork(): UnitOfWork;
    public function getUow(): UnitOfWork;

    // Create
    public function addCreated(WireEntityInterface $entity): void;
    public function clearCreateds(): bool;
    public function clearPersisteds(): bool;
    public function findCreated(string $euidOrUname): ?WireEntityInterface;
    public function insertEmbededStatus(WireEntityInterface $entity): void;
    public function createEntity(string $classname, array|false $data = false, array $context = [], bool $tryService = true): WireEntityInterface;
    public function createModel(string $classname, array|false $data = false, array $context = [], bool $tryService = true): WireEntityInterface;
    public function createClone(WireEntityInterface $entity, array $changes = [], array $context = [], bool $tryService = true): WireEntityInterface|false;
    // Maintain database
    // Maintain database
    public function checkAllDatabase(?OpresultInterface $opresult = null, bool $repair = false): OpresultInterface;
    public function checkDatabase(string $classname, ?OpresultInterface $opresult = null, bool $repair = false): OpresultInterface;
    // Entity Events
    public function postLoaded(WireEntityInterface $entity): void;
    public function postCreated(WireEntityInterface $entity): void;

    // Find
    public function findEntityByEuid(string $euid): ?WireEntityInterface;
    public function findEntityByUname(string $uname): ?WireEntityInterface;
    public function getClassnameByUname(string $uname): ?string;
    public function getClassnameByEuidOrUname(string $euidOrUname): ?string;
    public function findEntityByUniqueValue(string $value): ?WireEntityInterface;
    public function getEntitiesCount(string $classname, array $criteria = []): int;

    // Liip
    public function getBrowserPath(
        WireImageInterface|WirePdfInterface $media,
        ?string $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): ?string;
}
