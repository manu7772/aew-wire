<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
// Symfony
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

interface WireEntityManagerInterface extends WireServiceInterface
{

    public const CLONE_METHOD_WIRE = 0;
    public const CLONE_METHOD_WITH = 1;
    public const CLONE_METHOD_WILD = 2;

    public function getAppWireService(): AppWireServiceInterface;
    public function getEntityService(string|WireEntityInterface $entity): WireEntityManagerInterface|WireEntityServiceInterface;
    public function getClassMetadata(null|string|WireEntityInterface $objectOrClass = null): ?ClassMetadata;
    public static function isAppWireEntity(string|object $objectOrClass): bool;
    public function getEntityNames(bool $asShortnames = false, bool $allnamespaces = false, bool $onlyInstantiables = false): array;
    public function entityExists(string $classname, bool $allnamespaces = false, bool $onlyInstantiables = false): bool;
    public static function getConstraintUniqueFields(string $classname, bool|null $flatlisted = false): array;
    public function getRelateds(string|WireEntityInterface $objectOrClass, string|array|null $relationTypes = null, bool $excludeSelf = false): array;

    // Persist
    public function persist(WireEntityInterface $entity, bool $flush = false): static;
    public function update(WireEntityInterface $entity, bool $flush = false): static;
    public function remove(WireEntityInterface $entity, bool $flush = false): static;
    public function flush(): static;
    // Actions only used by entity service
    public function __innerPersist(WireEntityInterface $entity, bool $flush = false): static;
    public function __innerUpdate(WireEntityInterface $entity, bool $flush = false): static;
    public function __innerRemove(WireEntityInterface $entity, bool $flush = false): static;

    // Create
    public function createEntity(string $classname, ?string $uname = null): WireEntityInterface;
    public function createModel(string $classname): WireEntityInterface;
    public function createClone(WireEntityInterface $entity, ?string $uname = null, int $clone_method = 1): ?WireEntityInterface;
    // Actions only used by entity service
    public function __innerCreateEntity(string $classname, ?string $uname = null): WireEntityInterface; // ONLY USED BY ENTITY SERVICE
    public function __innerCreateModel(string $classname): WireEntityInterface; // ONLY USED BY ENTITY SERVICE
    public function __innerCreateClone(WireEntityInterface $entity, ?string $uname = null, int $clone_method = 1): ?WireEntityInterface; // ONLY USED BY ENTITY SERVICE

    // Find
    public function getRepository(string $classname, ?string $field = null): BaseWireRepositoryInterface;
    public function findEntityByEuid(string $euid): ?WireEntityInterface;
    public function findEntityByUname(string $uname): ?WireEntityInterface;
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