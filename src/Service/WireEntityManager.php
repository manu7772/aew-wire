<?php
namespace Aequation\WireBundle\Service;

// Aequation

use Aequation\WireBundle\Attribute\AdminGroup;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Component\WireClassMetadataManager;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Service\interface\CacheServiceInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataCollectionInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Service\interface\HydrationServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Dto\interface\WireEntityDtoInterface;
use Aequation\WireBundle\Entity\interface\TraitDatetimedInterface;
use Aequation\WireBundle\Entity\interface\TraitOwnerInterface;
use Aequation\WireBundle\Entity\interface\TraitWebpageableInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Service\interface\SurveyRecursionInterface;
use Aequation\WireBundle\Tools\HttpRequest;
use Aequation\WireBundle\Tools\Objects;
use Aequation\WireBundle\Service\interface\WireWebpageServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Service\interface\WireLanguageServiceInterface;
// Symfony
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Psr\Log\LoggerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
// PHP
use Exception;

/**
 * Class WireEntityManager
 * @package Aequation\WireBundle\Service
 */
#[AsAlias(WireEntityManagerInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class WireEntityManager implements WireEntityManagerInterface
{
    use TraitBaseService;

    // Criteria
    public const CRITERIA_ENABLED = ['enabled' => true];
    public const CRITERIA_DISABLED = ['enabled' => false];

    protected readonly UnitOfWork $uow;
    protected array $postFlushInfos = [];
    protected array $relatedDependencies = [];
    protected readonly WireClassMetadataManagerInterface $entitiesMetadata;

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
        public readonly EntityManagerInterface $em,
        public readonly AppWireServiceInterface $appWire,
        public readonly CacheServiceInterface $cacheService,
        protected UploaderHelper $vichHelper,
        protected CacheManager $liipCache,
        public readonly ValidatorInterface $validator,
        public readonly LoggerInterface $logger,
        public readonly SurveyRecursionInterface $surveyRecursion,
    ) {
    }


    public function getHydrationService(): HydrationServiceInterface
    {
        return $this->appWire->get(HydrationServiceInterface::class);
    }

    public function isDev(): bool
    {
        return $this->appWire->isDev();
    }

    public function isProd(): bool
    {
        return $this->appWire->isProd();
    }


    /****************************************************************************************************/
    /** SERVICES                                                                                        */
    /****************************************************************************************************/

    /**
     * get AppWireService
     *
     * @return AppWireServiceInterface
     */
    public function getAppWireService(): AppWireServiceInterface
    {
        return $this->appWire;
    }

    /**
     * get entity service
     *
     * @param string|BaseEntityInterface $entity
     * @return ?WireEntityServiceInterface
     */
    public function getEntityService(string|BaseEntityInterface $entity): ?WireEntityServiceInterface
    {
        return $this->getEntitiesMetadata()->getService($entity);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getEm(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->uow ??= $this->em->getUnitOfWork();
    }

    public function getUow(): UnitOfWork
    {
        return $this->getUnitOfWork();
    }


    /****************************************************************************************************/
    /** GENERATION                                                                                      */
    /****************************************************************************************************/

    public function isGrantsCheckEnabled(): bool
    {
        if(HttpRequest::isCli() || $this->appWire->isXmlHttpRequest()) {
            return false;
        }
        if($this->appWire->isProd()) {
            return true; // Enable grants check in PROD environment
        }
        return true;
    }

    /**
     * create entity
     * 
     * @param string $classname
     * @param string|null $uname
     * @return object
     */
    public function createEntity(string $classname, array $data = [], array $options = []): object
    {
        // $this->surveyRecursion->survey(__METHOD__.'::'.$classname);
        /** @var BaseEntityInterface $entity */
        $entity = $this->getEntitiesMetadata()->newInstance($classname, $data, $options);
        if($this->isGrantsCheckEnabled() && !$this->appWire->isGranted('new', $entity->getClassname())) {
            throw new Exception(vsprintf('Error %s line %d: you are not allowed to create %s (firewall is %s)!', [__METHOD__, __LINE__, $classname, $this->appWire->getFirewallName()]));
        }
        return $entity;
    }

    /**
     * create model
     * 
     * @return BaseEntityInterface
     */
    public function createModel(string $classname, array $data = [], array $options = []): BaseEntityInterface
    {
        // $this->surveyRecursion->survey(__METHOD__.'::'.$classname);
        $model = $this->getEntitiesMetadata()->newModel($classname, $data, $options);
        return $model;
    }

    /**
     * create clone
     * 
     * @return BaseEntityInterface|null
     */
    public function createClone(BaseEntityInterface $entity, array $changes = [], array $options = []): BaseEntityInterface|false
    {
        if($this->isGrantsCheckEnabled() && !$this->appWire->isGranted('new', $entity)) {
            throw new Exception(vsprintf('Error %s line %d: you are not allowed to clone %s!', [__METHOD__, __LINE__, $entity]));
        }
        throw new Exception('Not implemented yet!');
    }

    public function createDto(
        string $classname,
        array $data = [],
        array $options = []
    ): ?WireEntityDtoInterface
    {
        // $this->surveyRecursion->survey(__METHOD__.'::'.$classname);
        // $wCmd = $this->getEntitiesMetadata()->findOneInstantiable([$classname]);
        $dto = $this->getEntitiesMetadata()->newDto($classname, $data, $options);
        return $dto;
    }


    /****************************************************************************************************/
    /** VALIDATION                                                                                      */
    /****************************************************************************************************/

    public function validateEntity(object $entity, string|GroupSequence|array|null $addGroups = null, Constraint|array|null $constraints = null, bool $throws = false): ConstraintViolationListInterface
    {
        $errors = $this->getEntitiesMetadata()->validateEntity($entity, $addGroups, $constraints);
        if($throws && $errors->count() > 0) {
            throw new Exception(vsprintf('Error %s line %d:%s%s', [__METHOD__, __LINE__, PHP_EOL.'On '.Objects::toDebugString($entity), PHP_EOL.'- '.implode(PHP_EOL.'- ', Objects::violationListToArray($errors))]));
        }
        return $errors;
    }

    /****************************************************************************************************/
    /** REPOSITORY / QUERYS WITH IDENTITY (EUID, UNAME, ...)                                            */
    /****************************************************************************************************/

    public function getRepository(string|object $objectOrClass): ?EntityRepository
    {
        return $this->getEntitiesMetadata()->getRepository($objectOrClass);
    }

    // /**
    //  * get repository
    //  * 
    //  * @param string $classname
    //  * @param string|null $field
    //  * @return BaseWireRepositoryInterface
    //  */
    // public function getRepository(
    //     string $classname,
    //     ?string $field = null // if field, find repository where is declared this $field
    // ): BaseWireRepositoryInterface
    // {
    //     $cmd = $this->getClassMetadata($classname);
    //     $classname = $cmd->name;
    //     if($field) {
    //         // Find classname where field is declared
    //         if(array_key_exists($field, $cmd->fieldMappings)) {
    //             $test_classname = $cmd->fieldMappings[$field]->declared ?? $classname;
    //         } else if(array_key_exists($field, $cmd->associationMappings)) {
    //             $test_classname = $cmd->associationMappings[$field]->declared ?? $classname;
    //         } else {
    //             // Not found, tant pis...
    //         }
    //         if(isset($test_classname)) {
    //             $test_cmd = $this->getClassMetadata($test_classname);
    //             if(!$test_cmd->isMappedSuperclass) $classname = $test_classname;
    //         }
    //     }
    //     /** @var BaseWireRepositoryInterface */
    //     $repo = $this->em->getRepository($classname);
    //     // if(!empty($field)) dump($classname, $field, get_class($repo));
    //     if($this->isDev() && !($repo instanceof BaseWireRepositoryInterface)) {
    //         dd($this->__toString(), $classname, $cmd, $cmd->name, $repo);
    //     }
    //     return $repo;
    // }

    /**
     * find entity by id
     * 
     * @param int|string $id
     * @return BaseEntityInterface|null
     */
    public function findById(string $classname, string $id): ?BaseEntityInterface
    {
        $repo = $this->em->getRepository($classname);
        return $repo->find($id);
    }

    public function findByEuid(string $euid): ?BaseEntityInterface
    {
        $class = Encoders::getClassOfEuid($euid);
        $repo = $this->em->getRepository($class);
        $entity = $repo->findOneBy(['euid' => $euid]);
        return $entity instanceof BaseEntityInterface ? $entity : null;
    }

    public function euidExists(string $euid, bool $getData = false): bool|null|array
    {
        $class = Encoders::getClassOfEuid($euid);
        $repo = $this->em->getRepository($class);
        $entity = $repo
            ->createQueryBuilder('u')
            ->select('u.id, u.classname','u.euid')
            ->where('u.euid = :euid')
            ->setParameter('euid', $euid)
            ->getQuery()
            ->getScalarResult()
            ;
        if(empty($entity)) $entity = null;
        return $getData ? $entity : !empty($entity);
    }

    public function findByUname(string $uname): ?BaseEntityInterface
    {
        $unameOjb = $this->getRepository(Uname::class)->find($uname);
        $entity = $unameOjb instanceof UnameInterface
            ? $this->findByEuid($unameOjb->getEntityEuid())
            : null;
        return $entity instanceof BaseEntityInterface ? $entity : null;
    }

    public function findUnameByUname(string $uname): ?UnameInterface
    {
        return $this->findById(Uname::class, $uname);
    }

    public function getEuidOfUname(string $uname): ?string
    {
        if(Encoders::isUnameFormatValid($uname) || Encoders::isEuidFormatValid($uname)) {
            $unameOjb ??= $this->getRepository(Uname::class)->findOneById($uname);
            if($unameOjb instanceof UnameInterface) {
                $euid = $unameOjb->getEntityEuid();
                if(Encoders::isEuidFormatValid($euid)) return $euid;
            }
        }
        return null;
    }

    public function findByUniqueValue(string $value): ?BaseEntityInterface
    {
        return Encoders::isEuidFormatValid($value)
            ? $this->findByEuid($value)
            : $this->findByUname($value);
    }

    public function getClassnameByUname(string $uname): ?string
    {
        return $this->getRepository(Uname::class)->getClassnameByUname($uname);
    }

    public function getClassnameByEuidOrUname(string $euidOrUname): ?string
    {
        return Encoders::isEuidFormatValid($euidOrUname)
            ? Encoders::getClassOfEuid($euidOrUname)
            : $this->getClassnameByUname($euidOrUname);
    }

    public function findEntityByUname(string $uname): ?TraitUnamedInterface
    {
        $unameOjb = $this->getRepository(Uname::class)->findOneById($uname);
        if($unameOjb instanceof UnameInterface) {
            return $this->findByEuid($unameOjb->getEntityEuid());
        }
        return null;
    }


    /************************************************************************************************************/
    /** CLASS METADATA SHORTCUTS                                                                                */
    /************************************************************************************************************/

    public function entityExists(string $classname, bool $searchShortname = false): bool
    {
        $classnames = $this->getEntitiesMetadata()->filterClasses()->mapSingleValue($searchShortname ? 'shortname' : 'name');
        return in_array($classname, $classnames, true) || array_key_exists($classname, $classnames);
    }

    public function findOneFinal(string|object $entity): WireClassMetadataInterface
    {
        return $this->getEntitiesMetadata()->findOneFinal([$entity]);
    }

    public function findOneManaged(string|object $entity): WireClassMetadataInterface
    {
        return $this->getEntitiesMetadata()->findOneManaged([$entity]);
    }

    public function findOneInstantiable(string|object $entity): WireClassMetadataInterface
    {
        return $this->getEntitiesMetadata()->findOneInstantiable([$entity]);
    }

    public function getSerializableClassMetadatas(): WireClassMetadataCollectionInterface
    {
        return $this->getEntitiesMetadata()->findFinals([WireHydratable::class]);
    }


    /************************************************************************************************************/
    /** DATABASE REQUESTS                                                                                       */
    /************************************************************************************************************/

    public function count(string $classname, bool|array $criteria = []): int
    {
        if($service = $this->getEntityService($classname)) {
            return $service->count($criteria);
        }
        if(is_bool($criteria)) {
            $criteria = true === $criteria ? static::getCriteriaEnabled($classname) : static::getCriteriaDisabled($classname);
        }
        /** @var BaseWireRepositoryInterface */
        $repo = $this->em->getRepository($classname);
        return $repo->count($criteria);
    }

    public function findAll(string $classname, bool|array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        if($service = $this->getEntityService($classname)) {
            /** @var WireEntityServiceInterface $service */
            return $service->findAll($criteria, $orderBy, $limit, $offset);
        }
        if(is_bool($criteria)) {
            $criteria = $criteria ? static::getCriteriaEnabled($classname) : static::getCriteriaDisabled($classname);
        }
        $entities = $this->getRepository($classname)->findBy($criteria, $orderBy, $limit, $offset);
        return array_filter($entities, function ($entity) {
            if ($entity instanceof TraitEnabledInterface) {
                return $entity->isActive();
            }
            return true;
        });
    }

    public function findOneBy(string $classname, int|string $identifier, bool|array $criteria = [], ?array $orderBy = null): ?object
    {
        if($service = $this->getEntityService($classname)) {
            return $service->findOneBy($identifier, $criteria, $orderBy);
        }
        if(is_bool($criteria)) {
            $criteria = $criteria ? static::getCriteriaEnabled($classname) : static::getCriteriaDisabled($classname);
        }
        if(is_int($identifier) && $identifier > 0) {
            $criteria['id'] = $identifier;
        } else if(Encoders::isEuidFormatValid($identifier)) {
            $criteria['euid'] = $identifier;
        } elseif(Encoders::isUnameFormatValid($identifier)) {
            $euid = $this->getEuidOfUname($identifier);
            if(empty($euid)) {
                throw new Exception(vsprintf('Error %s line %d: could not resolve euid with uname %s for class %s!', [__METHOD__, __LINE__, $identifier, $classname]));
            }
            $criteria['euid'] = $euid;
        } else {
            throw new Exception(vsprintf('Error %s line %d: identifier "%s" is not valid!', [__METHOD__, __LINE__, $identifier]));
        }
        // $criteria_object = Criteria::create();
        // foreach ($criteria as $key => $value) {
        //     $criteria_object->andWhere(Criteria::expr()->eq($key, $value));
        // }
        $entity = $this->getRepository($classname)->findOneBy($criteria, $orderBy);
        if($entity instanceof TraitEnabledInterface) {
            return $entity->isActive() ? $entity : null;
        }
        return $entity;
    }


    /************************************************************************************************************/
    /** CRITERIA                                                                                                */
    /************************************************************************************************************/

    public static function getCriteriaEnabled(string $classname): array
    {
        return is_a($classname, TraitEnabledInterface::class, true) ? static::CRITERIA_ENABLED : [];
        // return is_a($classname, TraitEnabledInterface::class, true) ? [Criteria::expr()->eq('enabled', true)] : [];
    }

    public static function getCriteriaDisabled(string $classname): array
    {
        return is_a($classname, TraitEnabledInterface::class, true) ? static::CRITERIA_DISABLED : [];
        // return is_a($classname, TraitEnabledInterface::class, true) ? [Criteria::expr()->eq('enabled', false)] : [];
    }


    /************************************************************************************************************/
    /** ENTITY DEFAULT EVENTS                                                                                   */
    /************************************************************************************************************/

    public function defaultEntityEventActions(BaseEntityInterface $entity, ?OpresultInterface $opresult = null): void
    {
        // ... Default event actions for entity
        if($entity->getSelfState()->isNew()) {
            // After created actions...
            $this->checkEntity_datetimed($entity, $opresult, true);
            $this->checkEntity_owner($entity, $opresult, true);
            $this->checkEntity_webpageable($entity, true, $opresult, true);
        }
        if($entity->getSelfState()->isLoaded()) {
            // After loaded actions...
        }
        // After all actions...
    }

    public function defaultEntityCheckActions(BaseEntityInterface $entity, ?OpresultInterface $opresult = null, bool $repair = false): void
    {
        if($entity->getSelfState()->isLoaded()) {
            // Check webpageable entity
            $this->checkEntity_datetimed($entity, $opresult, $repair);
            $this->checkEntity_owner($entity, $opresult, $repair);
            $this->checkEntity_webpageable($entity, true, $opresult, $repair);
        } else {
            throw new Exception(vsprintf('Error %s line %d: check entity does not works with unloaded entities (got %s)!', [__METHOD__, __LINE__, Objects::toDebugString($entity)]));
        }
    }

    protected function checkEntity_datetimed(
        BaseEntityInterface $entity,
        ?OpresultInterface $opresult = null,
        bool $repair = false,
    ): void
    {
        if($entity instanceof TraitDatetimedInterface) {
            if(empty($entity->getLanguage()) && $repair) {
                /** @var WireLanguageServiceInterface */
                $languageService = $this->getEntityService(WireLanguageServiceInterface::class);
                $entity->setLanguage($languageService->getPreferedLanguage());
                $opresult->addSuccess(vsprintf('Language %s added to %s!', [$entity->getLanguage()->getName(), Objects::toDebugString($entity)]));
            }
            if(empty($entity->getTimezone()) && $repair) {
                if($entity->getLanguage()) {
                    $entity->setTimezone($entity->getLanguage()->getTimezone());
                } else {
                    /** @var WireLanguageServiceInterface */
                    $languageService ??= $this->getEntityService(WireLanguageServiceInterface::class);
                    if(($language = $languageService->getPreferedLanguage()) && $language->getTimezone()) {
                        $entity->setTimezone($language->getTimezone());
                        $opresult->addSuccess(vsprintf('Timezone %s added to %s!', [$entity->getTimezone(), Objects::toDebugString($entity)]));
                    }
                }
            }
        }
    }

    protected function checkEntity_owner(
        BaseEntityInterface $entity,
        ?OpresultInterface $opresult = null,
        bool $repair = false,
    ): void
    {
        if($entity instanceof TraitOwnerInterface && empty($entity->getOwner())) {
            /** @var WireUserServiceInterface */
            $userService = $this->getEntityService(WireUserInterface::class);
            $admin ??= $userService->getMainAdminUser(true);
            if(empty($admin)) {
                if($entity->isOwnerRequired()) {
                    $opresult->addDanger(vsprintf('%s needs a owner, but no main (s)admin found!', [Objects::toDebugString($entity)]));
                }
            } else if($entity->isOwnerRequired() && $repair) {
                $entity->setOwner($admin);
                $opresult->addSuccess(vsprintf('Owner %s added to %s %s!', [Objects::toDebugString($admin), TraitOwnerInterface::class, Objects::toDebugString($entity)]));
            } else if($entity->isOwnerRequired()) {
                $opresult->addError(vsprintf('%s requires an owner!', [Objects::toDebugString($entity)]));
            }
        }
    }

    protected function checkEntity_webpageable(
        BaseEntityInterface $entity,
        bool $onlyActiveWebpage = true,
        ?OpresultInterface $opresult = null,
        bool $repair = false,
    ): void
    {
        if($entity instanceof TraitWebpageableInterface && (empty($entity->getWebpage()) || ($onlyActiveWebpage && !$entity->getWebpage()->isActive()))) {
            /** @var WireWebpageServiceInterface */
            $webpageService = $this->getEntityService(WireWebpageInterface::class);
            $webpageService->getFirstExposableWebpage($entity, $onlyActiveWebpage, $repair);
            if ($opresult) {
                if($entity->getWebpage() && $entity->getWebpage()->isActive()) {
                    $opresult->addSuccess(vsprintf('Added active webpage %s to entity %s', [$entity->getWebpage(), Objects::toDebugString($entity)]));
                } else if($entity->isWebpageRequired()) {
                    $opresult->addWarning(vsprintf('Could not find any active webpage for entity %s', [Objects::toDebugString($entity)]));
                }
            }
        }
    }

    /************************************************************************************************************/
    /** ENTITY INFO                                                                                             */
    /************************************************************************************************************/

    // public function addPostFlushInfos(PostFlushEventArgs $args): void
    // {
    //     $this->postFlushInfos[] = $args->getObjectManager();
    // }

    // public function getPostFlushInfos(bool $getLastOnly = false): array
    // {
    //     return $getLastOnly ? end($this->postFlushInfos) : $this->postFlushInfos;
    // }

    /**
     * get entities [Wire]Metadata
     * 
     * @return WireClassMetadataManagerInterface
     */
    public function getEntitiesMetadata(): WireClassMetadataManagerInterface
    {
        return $this->entitiesMetadata ??= new WireClassMetadataManager($this);
    }

    public function getEntityMetadata(string|object $objectOrClass): WireClassMetadataInterface
    {
        return $this->getEntitiesMetadata()->getWireClassMetadata($objectOrClass);
    }


    /************************************************************************************************************/
    /** VICH IMAGE / LIIP IMAGE                                                                                 */
    /************************************************************************************************************/

    /**
     * get browser path
     * 
     * @param WireImageInterface|WirePdfInterface $media
     * @param string|null $filter
     * @param array $runtimeConfig
     * @param mixed $resolver
     * @param int $referenceType
     * @return string|null
     */
    public function getBrowserPath(WireImageInterface|WirePdfInterface $media, ?string $filter = null, array $runtimeConfig = [], $resolver = null, $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): ?string
    {
        $browserPath = $this->vichHelper->asset($media);
        if ($filter && !($media instanceof WirePdfInterface)) {
            $browserPath = $this->liipCache->getBrowserPath($browserPath, $filter, $runtimeConfig, $resolver, $referenceType);
        }
        return $browserPath;
    }


}
