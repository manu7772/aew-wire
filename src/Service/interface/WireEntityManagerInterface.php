<?php

namespace Aequation\WireBundle\Service\interface;

// Aequation
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Markup;
// PHP
use Closure;

interface WireEntityManagerInterface extends WireServiceInterface
{

    public function getNormaliserService(): NormalizerServiceInterface;
    public function getAppWireService(): AppWireServiceInterface;
    public function getEntityService(string|WireEntityInterface $entity): ?WireEntityServiceInterface;
    public function getClassMetadata(null|string|WireEntityInterface $objectOrClass = null): ?ClassMetadata;
    public function getRepository(string|WireEntityInterface $objectOrClass): ?EntityRepository;
    public static function isAppWireEntity(string|object $objectOrClass): bool;
    public function getEntityNames(bool $asShortnames = false, bool $allnamespaces = false, bool $onlyInstantiables = false): array;
    public function getEntityNamesChoices(bool $asHtml = false, string|false $icon_type = 'fa', bool $allnamespaces = false, bool $onlyInstantiables = false): array;
    public function getEntityNameAsHtml(string|WireEntityInterface $classOrEntity, string|false $icon_type = false, bool $addClassname = true): Markup;
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
    // public function checkEntityBase(WireEntityInterface $entity): void;
    public function createEntity(string $classname, array|false $data = false, array $context = [], bool $tryService = true): WireEntityInterface;
    public function createModel(string $classname, array|false $data = false, array $context = [], bool $tryService = true): WireEntityInterface;
    public function createClone(WireEntityInterface $entity, array $changes = [], array $context = [], bool $tryService = true): WireEntityInterface|false;
    // Entity Events
    public function postLoaded(WireEntityInterface $entity): void;
    public function postCreated(WireEntityInterface $entity): void;

    // Find
    // public function getRepository(string $classname, ?string $field = null): BaseWireRepositoryInterface;
    public function findEntityByEuid(string $euid): ?WireEntityInterface;
    public function findEntityByUname(string $uname): ?WireEntityInterface;
    public function findEntityByUniqueValue(string $value): ?WireEntityInterface;
    public function getEntitiesCount(string $classname, array $criteria = []): int;

    // Check
    // public function checkIntegrity(WireEntityInterface $entity, null|EventArgs|string $event = null): void;

    // Liip
    public function getBrowserPath(
        WireImageInterface|WirePdfInterface $media,
        ?string $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): ?string;
}
