<?php

namespace Aequation\WireBundle\Service\interface;

// Aequation

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataCollectionInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Dto\interface\WireEntityDtoInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
// Symfony
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Psr\Log\LoggerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
// PHP
use Closure;

interface WireEntityManagerInterface extends WireServiceInterface
{

    /**
     * constructor.
     * 
     * @param EntityManagerInterface $em
     * @param AppWireServiceInterface $appWire
     * @param CacheServiceInterface $cacheService
     * @param UploaderHelper $vichHelper
     * @param CacheManager $liipCache
     * @param LoggerInterface $logger
     * @param SurveyRecursionInterface $surveyRecursion
     */
    public function __construct(
        EntityManagerInterface $em,
        AppWireServiceInterface $appWire,
        CacheServiceInterface $cacheService,
        UploaderHelper $vichHelper,
        CacheManager $liipCache,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        SurveyRecursionInterface $surveyRecursion,
    );

    public function getHydrationService(): HydrationServiceInterface;
    public function isDev(): bool;
    public function isProd(): bool;
    public function getAppWireService(): AppWireServiceInterface;
    public function getEntityService(string|BaseEntityInterface $entity): ?WireEntityServiceInterface;
    public function getEntityManager(): EntityManagerInterface;
    public function getEm(): EntityManagerInterface;
    public function getUnitOfWork(): UnitOfWork;
    public function getUow(): UnitOfWork;
    public function isGrantsCheckEnabled(): bool;
    public function createEntity(string $classname, array $data = [], array $options = []): object;
    public function createModel(string $classname, array $data = [], array $options = []): BaseEntityInterface;
    public function createClone(BaseEntityInterface $entity, array $changes = [], array $options = []): BaseEntityInterface|false;
    public function createDto(string $classname, array $data = [], array $options = []): ?WireEntityDtoInterface;
    public function validateEntity(object $entity, string|GroupSequence|array|null $addGroups = null, Constraint|array|null $constraints = null, bool $throws = false): ConstraintViolationListInterface;
    public function getRepository(string|object $objectOrClass): ?EntityRepository;
    public function findById(string $classname, string $id): ?BaseEntityInterface;
    public function findByEuid(string $euid): ?BaseEntityInterface;
    public function euidExists(string $euid, bool $getData = false): bool|null|array;
    public function findByUname(string $uname): ?BaseEntityInterface;
    public function findUnameByUname(string $uname): ?UnameInterface;
    public function getEuidOfUname(string $uname): ?string;
    public function findByUniqueValue(string $value): ?BaseEntityInterface;
    public function getClassnameByUname(string $uname): ?string;
    public function getClassnameByEuidOrUname(string $euidOrUname): ?string;
    public function findEntityByUname(string $uname): ?TraitUnamedInterface;
    public function entityExists(string $classname, bool $searchShortname = false): bool;
    public function findOneFinal(string|object $entity): WireClassMetadataInterface;
    public function findOneManaged(string|object $entity): WireClassMetadataInterface;
    public function findOneInstantiable(string|object $entity): WireClassMetadataInterface;
    public function getSerializableClassMetadatas(): WireClassMetadataCollectionInterface;
    public function count(string $classname, bool|array $criteria = []): int;
    public function findAll(string $classname, bool|array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
    public function findOneBy(string $classname, int|string $identifier, bool|array $criteria = [], ?array $orderBy = null): ?object;
    // Event & check actions
    public function defaultEntityEventActions(BaseEntityInterface $entity, ?OpresultInterface $opresult = null): void;
    public function defaultEntityCheckActions(BaseEntityInterface $entity, ?OpresultInterface $opresult = null, bool $repair = false): void;

    public function getEntitiesMetadata(): WireClassMetadataManagerInterface;
    public function getEntityMetadata(string|object $objectOrClass): WireClassMetadataInterface;
    public function getBrowserPath(WireImageInterface|WirePdfInterface $media, ?string $filter = null, array $runtimeConfig = [], $resolver = null, $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): ?string;
}
