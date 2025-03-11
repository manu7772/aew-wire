<?php
namespace Aequation\WireBundle\Service\interface;

// Aequation
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
// Symfony
use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

interface WireEntityManagerInterface extends WireServiceInterface
{

    public function getAppWireService(): AppWireServiceInterface;
    public function getEntityService(string|WireEntityInterface $entity): ?WireEntityServiceInterface;
    public function getClassMetadata(null|string|WireEntityInterface $objectOrClass = null): ?ClassMetadata;
    public static function isAppWireEntity(string|object $objectOrClass): bool;
    public function getEntityNames(bool $asShortnames = false, bool $allnamespaces = false, bool $onlyInstantiables = false): array;
    public function entityExists(string $classname, bool $allnamespaces = false, bool $onlyInstantiables = false): bool;
    public static function getConstraintUniqueFields(string $classname, bool|null $flatlisted = false): array;
    public function getRelateds(string|WireEntityInterface $objectOrClass, null|string|array $relationTypes = null, ?bool $excludeSelf = false): array;
    public function getEntityManager(): EntityManagerInterface;
    public function getEm(): EntityManagerInterface;
    public function getUnitOfWork(): UnitOfWork;
    public function getUow(): UnitOfWork;

    // Create
    public function addCreated(WireEntityInterface $entity): void;
    public function clearCreateds(): bool;
    public function clearPersisteds(): bool;
    public function findCreated(string $euidOrUname): ?WireEntityInterface;
    public function postCreatedRealEntity(WireEntityInterface $entity, bool $asModel = false): void;
    public function checkEntityBase(WireEntityInterface $entity): void;
    public function createEntity(string $classname, ?array $data = [], ?array $context = []): WireEntityInterface;
    public function createModel(string $classname,?array $data = [], ?array $context = []): WireEntityInterface;
    public function createClone(WireEntityInterface $entity, ?array $changes = [], ?array $context = []): WireEntityInterface|false;

    // Find
    // public function getRepository(string $classname, ?string $field = null): BaseWireRepositoryInterface;
    public function findEntityByEuid(string $euid): ?WireEntityInterface;
    public function findEntityByUname(string $uname): ?WireEntityInterface;
    public function findEntityByUniqueValue(string $value): ?WireEntityInterface;
    public function getEntitiesCount(string $classname, array $criteria = []): int;

    // Check
    public function checkIntegrity(WireEntityInterface $entity, null|EventArgs|string $event = null): void;

    // Liip
    public function getBrowserPath(
        WireImageInterface|WirePdfInterface $media,
        ?string $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): ?string;

}