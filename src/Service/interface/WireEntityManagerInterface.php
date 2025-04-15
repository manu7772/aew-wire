<?php

namespace Aequation\WireBundle\Service\interface;

// Aequation

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
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
use Doctrine\ORM\Event\PostFlushEventArgs;

interface WireEntityManagerInterface extends WireServiceInterface
{

    // Debug mode
    public function isDebugMode(): bool;
    public function incDebugMode(): bool;
    public function decDebugMode(): bool;
    public function resetDebugMode(): bool;

    public function getNormaliserService(): NormalizerServiceInterface;
    public function getAppWireService(): AppWireServiceInterface;
    public function getEntityService(string|BaseEntityInterface $entity): ?WireEntityServiceInterface;
    public function getClassMetadata(null|string|BaseEntityInterface $objectOrClass = null): ?ClassMetadata;
    public function addPostFlushInfos(PostFlushEventArgs $args): void;
    public function getPostFlushInfos(bool $getLastOnly = false): array;
    public function getRepository(string|BaseEntityInterface $objectOrClass): ?EntityRepository;
    public static function isAppWireEntity(string|object $objectOrClass): bool;
    public static function isBetweenEntity(string|object $objectOrClass): bool;
    public static function isTranslationEntity(string|object $objectOrClass): bool;
    public function getEntityNames(bool $asShortnames = false, bool $allnamespaces = false, bool $onlyInstantiables = false): array;
    public function getAppEntityNames(bool $asShortnames = false, bool $onlyInstantiables = false): array;
    public function getBetweenEntityNames(bool $asShortnames = false): array;
    public function getTranslationEntityNames(bool $asShortnames = false): array;
    public function getFinalEntities(bool $asShortnames = false, bool $allnamespaces = false): array;

    /**
     * Get all final entities classnames of interfaces
     * - if $allnamespaces is true, all namespaces are searched
     * - if $allnamespaces is false, only instances of BaseEntityInterface are searched
     * 
     * @param string|array $interfaces
     * @param bool $allnamespaces
     * @return array
     */
    public function resolveFinalEntitiesByNames(string|array $interfaces, bool $allnamespaces = false): array;
    public function getClassnameByShortname(string $shortname, bool $allnamespaces = false, bool $onlyInstantiables = false): ?string;
    public function entityExists(string $classname, bool $allnamespaces = false, bool $onlyInstantiables = false): bool;
    public static function getConstraintUniqueFields(string $classname, bool|null $flatlisted = false): array;
    public function getRelatedClassnames(string|BaseEntityInterface $objectOrClass, ?Closure $filter = null): array;
    public function getAllRelatedProperties(string|BaseEntityInterface $objectOrClass, array $filterFields = [], ?Closure $filter = null): array;
    public function getRelateds(string|BaseEntityInterface $objectOrClass, ?Closure $filter = null, bool $excludeSelf = false): array;
    public function getEntityManager(): EntityManagerInterface;
    public function getEm(): EntityManagerInterface;
    public function getUnitOfWork(): UnitOfWork;
    public function getUow(): UnitOfWork;

    // Create
    public function dismissCreateds(bool $dismissCreateds): void;
    public function isDismissCreateds(): bool;
    public function addCreated(BaseEntityInterface $entity): void;
    public function clearCreateds(): bool;
    public function clearPersisteds(): bool;
    public function findCreated(string $euidOrUname): ?BaseEntityInterface;
    public function insertEmbededStatus(BaseEntityInterface $entity): void;
    public function createEntity(string $classname, array|false $data = false, array $context = [], bool $tryService = true): BaseEntityInterface;
    public function createModel(string $classname, array|false $data = false, array $context = [], bool $tryService = true): BaseEntityInterface;
    public function createClone(BaseEntityInterface $entity, array $changes = [], array $context = [], bool $tryService = true): BaseEntityInterface|false;
    // Entity Events
    public function postLoaded(BaseEntityInterface $entity): void;
    public function postCreated(BaseEntityInterface $entity): void;

    // Find
    public function findEntityById(string $classname, string $id): ?BaseEntityInterface;
    /**
     * find entity by `euid`
     * 
     * @param string $euid
     * @return BaseEntityInterface|null
     */
    public function findEntityByEuid(string $euid): ?BaseEntityInterface;
    /**
     * find entity by `uname`
     * 
     * @param string $uname
     * @return BaseEntityInterface|null
     */
    public function findEntityByUname(string $uname): ?BaseEntityInterface;
    /**
     * Get `euid` of `uname`
     * 
     * @param string $uname
     * @return string|null
     */
    public function getEuidOfUname(string $uname): ?string;
    /**
     * find entity by unique value:
     * - `uname`
     * - `euid`
     * 
     * @param string $value
     * @return BaseEntityInterface|null
     */
    public function findEntityByUniqueValue(string $value): ?BaseEntityInterface;
    public function getClassnameByUname(string $uname): ?string;
    public function getClassnameByEuidOrUname(string $euidOrUname): ?string;
    /**
     * get count of entities
     * - uses criteria
     * - search *ONLY IN DATABASE*  
     * - if `$criteria` is boolean, it will be converted to criteria: true = active, false = inactive
     * 
     * @param bool|array $criteria
     * @return int
     */
    public function getEntitiesCount(
        string $classname,
        bool|array $criteria = []
    ): int;
    /**
     * get all entities
     * - uses criteria
     * - search *ONLY IN DATABASE*
     * - if `$criteria` is boolean, it will be converted to criteria: true = active, false = inactive
     * 
     * @param bool|array $criteria
     * @return array
     */
    public function findAllEntities(
        string $classname,
        bool|array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array;
    /**
     * get one entity by id or euid or uname
     * - uses criteria
     * - search *ONLY IN DATABASE*
     * - if `$criteria` is boolean, it will be converted to criteria: true = active, false = inactive
     * 
     * @param int|string $identifier
     * @param bool|array $criteria
     * @return object|null
     */
    public function findEntity(
        string $classname,
        int|string $identifier,
        bool|array $criteria = [],
        ?array $orderBy = null,
    ): ?object;

    // Criteria
    public static function getCriteriaEnabled(string $classname): array;
    public static function getCriteriaDisabled(string $classname): array;

    // Liip
    public function getBrowserPath(
        WireImageInterface|WirePdfInterface $media,
        ?string $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): ?string;
}
