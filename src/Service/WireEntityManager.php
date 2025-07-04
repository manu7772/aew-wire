<?php
namespace Aequation\WireBundle\Service;

// Aequation
use Aequation\WireBundle\Component\EntityContainer;
use Aequation\WireBundle\Component\WireClassMetadataManager;
use Aequation\WireBundle\Component\interface\EntityContainerInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitOwnerInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Entity\interface\TraitDatetimedInterface;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Entity\interface\TraitWebpageableInterface;
use Aequation\WireBundle\Entity\interface\WireLanguageInterface;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\CacheServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\SurveyRecursionInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireLanguageServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\HttpRequest;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Psr\Log\LoggerInterface;
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

    private array $__src = [];
    // Criteria
    public const CRITERIA_ENABLED = ['enabled' => true];
    public const CRITERIA_DISABLED = ['enabled' => false];

    protected readonly UnitOfWork $uow;
    public int $hydrate_mode = 0;
    protected array $postFlushInfos = [];
    protected array $relatedDependencies = [];
    protected readonly WireClassMetadataManagerInterface $entitiesMetadata;
    protected bool $useService = true;

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


    public function getNormaliserService(): NormalizerServiceInterface
    {
        return $this->appWire->get(NormalizerServiceInterface::class);
    }


    /****************************************************************************************************/
    /** HYDRATE MODE                                                                                    */
    /****************************************************************************************************/

    public function isHydrateMode(): bool
    {
        return $this->hydrate_mode > 0 || HttpRequest::isCli();
    }
    
    public function incHydrateMode(): bool
    {
        $this->hydrate_mode++;
        return $this->isHydrateMode();
    }

    public function decHydrateMode(): bool
    {
        $this->hydrate_mode--;
        return $this->isHydrateMode();
    }

    public function resetHydrateMode(): bool
    {
        $this->hydrate_mode = 0;
        return $this->isHydrateMode();
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
    public function getEntityService(
        string|BaseEntityInterface $entity
    ): ?WireEntityServiceInterface
    {
        if(is_string($entity)) {
            if($classname = $this->resolveFinalEntity($entity, false)) {
                return $this->appWire->getClassService($classname);
            }
            // $classnames = $this->resolveFinalEntitiesByNames($entity, false);
            // foreach ($classnames as $classname) {
            //     if($service = $this->appWire->getClassService($classname)) {
            //         // dd($entity.' => '.implode(' / ', $classnames).' => Class: '.$classname.' => Service: '.$service);
            //         return $service;
            //     }
            // }
            throw new Exception(vsprintf('Error %s line %d: no final entity found for class or interface %s!', [__METHOD__, __LINE__, $entity]));
        }
        return $this->appWire->getClassService($entity);
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

    public function disableUseService(): static
    {
        $this->useService = false;
        return $this;
    }

    /**
     * create entity
     * 
     * @param string $classname
     * @param string|null $uname
     * @return BaseEntityInterface
     */
    public function createEntity(
        string $classname,
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = []
    ): BaseEntityInterface {
        $this->surveyRecursion->survey(__METHOD__.'::'.$classname);
        $classname = $this->resolveFinalEntity($classname, false);
        if(!class_exists($classname)) {
            throw new Exception(vsprintf('Error %s line %d: class %s not found!', [__METHOD__, __LINE__, $classname]));
        }
        if($this->useService && $service = $this->getEntityService($classname)) {
            return $service->createEntity($data, $context);
        }
        $this->useService = true; // Reset useService for next calls
        if(!$data || empty($data)) {
            $entity = new $classname();
            $this->postCreated($entity);
        } else {
            // Denormalize
            $context[EntityContainerInterface::CONTEXT_DO_NOT_UPDATE] = false;
            $context[EntityContainerInterface::CONTEXT_AS_MODEL] = false;
            $normalizeContainer = new EntityContainer($this->getNormaliserService(), $classname, $data, $context);
            $entity = $this->getNormaliserService()->denormalizeEntity($normalizeContainer, $classname);
        }
        // Add some stuff here...
        return $entity;
    }

    /**
     * create model
     * 
     * @return BaseEntityInterface
     */
    public function createModel(
        string $classname,
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = []
    ): BaseEntityInterface {
        $this->surveyRecursion->survey(__METHOD__.'::'.$classname);
        if($this->useService && $service = $this->getEntityService($classname)) {
            return $service->createModel($data, $context);
        }
        $this->useService = true; // Reset useService for next calls
        if(!$data || empty($data)) {
            $model = new $classname();
            $model->getSelfState()->setModel();
            $this->postCreated($model);
        } else {
            // Denormalize
            $context[EntityContainerInterface::CONTEXT_DO_NOT_UPDATE] = true;
            $context[EntityContainerInterface::CONTEXT_AS_MODEL] = true;
            $normalizeContainer = new EntityContainer($this->getNormaliserService(), $classname, $data, $context);
            $model = $this->getNormaliserService()->denormalizeEntity($normalizeContainer, $classname);
        }
        // Add some stuff here...
        return $model;
    }

    /**
     * create clone
     * 
     * @return BaseEntityInterface|null
     */
    public function createClone(
        BaseEntityInterface $entity,
        ?array $changes = [], // ---> do not forget uname if wanted!
        ?array $context = []
    ): BaseEntityInterface|false {
        throw new Exception('Not implemented yet!');

        $this->surveyRecursion->survey(__METHOD__.'::'.$entity->getClassname());

        $this->useService = true; // Reset useService for next calls
        // ...
    }


    /****************************************************************************************************/
    /** ENTITY EVENTS                                                                                   */
    /****************************************************************************************************/

    /**
     * After a entity is loaded from database
     * 
     * @param BaseEntityInterface $entity
     * @return void
     */
    public function postLoaded(BaseEntityInterface $entity): void
    {
        $entity->initializeSelfstate();
        if(!$entity->getSelfState()->isExactState('loaded')) {
            if($entity->getSelfState()->isModel()) {
                throw new Exception(vsprintf('Error %s line %d: entity %s is a model, not an entity!', [__METHOD__, __LINE__, $entity->getClassname()]));
            }
            throw new Exception(vsprintf('Error %s line %d: entity %s is not loaded ONLY!', [__METHOD__, __LINE__, $entity->getClassname()]));
        }
        if($entity->getSelfState()->isPostLoaded() ?? false) {
            // Entity loaded events already done
            $message = vsprintf('%s line %d: %s (id: %s) already %s!', [__METHOD__, __LINE__, $entity->getClassname(), $entity->getId() ?? 'NULL', __FUNCTION__]);
            if($this->isDev()) {
                throw new Exception('Error '.$message);
            }
            $this->logger->warning('Debug '.$message);
            return;
        }
        // Prepare Embedded status
        $entity->getSelfState()->startEmbed($this->appWire, false);
        // First apply internal events
        $entity->getSelfState()->applyEvents();
        // Then apply external events
        // ...
        $this->defaultEventActions($entity, __FUNCTION__);
    }

    /**
     * After a new entity created
     * 
     * @param BaseEntityInterface $entity
     * @return void
     */
    public function postCreated(BaseEntityInterface $entity): void
    {
        if(!$entity->getSelfState()->isExactState('new')) {
            if(!$entity->getSelfState()->isModel()) {
                // dump($entity->getSelfState()->getReport());
                throw new Exception(vsprintf('Error %s line %d: entity %s is not new ONLY (and not a MODEL either)!', [__METHOD__, __LINE__, $entity->getClassname()]));
            }
        }
        if($entity->getSelfState()->isPostCreated() ?? false) {
            // Entity created events already done
            $message = vsprintf('%s line %d: %s (id: %s) already %s!', [__METHOD__, __LINE__, $entity->getClassname(), $entity->getId() ?? 'NULL', __FUNCTION__]);
            if($this->isDev()) {
                throw new Exception('Error '.$message);
            }
            $this->logger->warning('Debug '.$message);
            return;
        }
        // Prepare Embedded status
        $entity->getSelfState()->startEmbed($this->appWire, false);
        // First apply internal events
        $entity->getSelfState()->applyEvents();
        // Then apply external events
        $this->defaultEventActions($entity, __FUNCTION__);
    }

    private function defaultEventActions(
        BaseEntityInterface $entity,
        string $eventName,
    ): void
    {
        if($entity->getSelfState()->isModel()) return;
        $actions = [
            TraitOwnerInterface::class => [
                // 'postLoaded',
                'postCreated',
            ],
            TraitWebpageableInterface::class => [
                // 'postLoaded',
                'postCreated',
            ],
            TraitUnamedInterface::class => [
                // 'postLoaded',
                'postCreated',
            ],
            TraitDatetimedInterface::class => [
                // 'postLoaded',
                'postCreated',
            ],
        ];
        foreach ($actions as $interface => $triggers) {
            if(in_array($eventName, $triggers) && is_a($entity, $interface)) {
                switch ($interface) {
                    case TraitOwnerInterface::class:
                        /** @var TraitOwnerInterface $entity */
                        if(empty($entity->getOwner())) {
                            $user = $this->appWire->getUser();
                            if ($user) {
                                $entity->setOwner($user);
                            } else if ($entity->isOwnerRequired()) {
                                $userService = $this->appWire->get(WireUserServiceInterface::class);
                                $admin = $userService->getMainAdmin();
                                if ($admin) {
                                    $entity->setOwner($admin);
                                } else if ($this->isDev()) {
                                    throw new Exception(vsprintf('Error %s line %d: entity %s %s has no owner!', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
                                }
                            }
                        }
                        break;
                    case TraitWebpageableInterface::class:
                        /** @var TraitWebpageableInterface $entity */
                        $unames = [
                            'User' => 'wp_user_presentation',
                        ];
                        $uname = $unames[$entity->getShortname()] ?? null;
                        if($uname && empty($entity->getWebpage()) && ($webpage = $this->findByUname($uname))) {
                            if($webpage->getEmbededStatus()->isContained()) $entity->setWebpage($webpage);
                        }
                        break;
                    case TraitDatetimedInterface::class:
                        /** @var TraitDatetimedInterface $entity */
                        if(empty($entity->getLanguage())) {
                            if($defaultLanguage = $this->appWire->getCurrentLanguage()) {
                                $entity->setLanguage($defaultLanguage);
                                // Default timezone setted automatically
                            } else {
                                /** @var WireLanguageServiceInterface */
                                $service = $this->getEntityService(WireLanguageInterface::class);
                                if($prefered = $service->getPreferedLanguage()) {
                                    $entity->setLanguage($prefered);
                                // } else {
                                //     foreach ($this->getNormaliserService()->getCreateds() as $ent) {
                                //         if($ent instanceof WireLanguageInterface && $ent->isPrefered()) {
                                //             $entity->setLanguage($ent);
                                //         }
                                //     }
                                }
                            }
                        }
                        // if($this->isDev() && (empty($entity->getTimezone()) || empty($entity->getLanguage()))) {
                        //     throw new Exception(vsprintf('Error %s line %d: entity %s has no timezone or language!', [__METHOD__, __LINE__, Objects::toDebugString($entity)]));
                        // }
                        break;
                    case TraitUnamedInterface::class:
                        /** @var TraitUnamedInterface $entity */
                        if(empty($entity->getUname())) {
                            $this->postCreated($entity->getUname());
                        }
                        break;
                }
            }
        }
    }

    public function validateEntity(
        BaseEntityInterface $entity,
        array $addGroups = [],
        Constraint|array|null $constraints = null
    ): ConstraintViolationListInterface
    {
        $groups = $entity->__selfstate->isNew() ? ['persist'] : ['update'];
        return $this->validator->validate($entity, $constraints, array_unique(array_merge($groups, $addGroups)));
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
    public function findById(
        string $classname,
        string $id
    ): ?BaseEntityInterface
    {
        $repo = $this->em->getRepository($classname);
        return $repo->find($id);
    }

    public function findByEuid(
        string $euid
    ): ?BaseEntityInterface
    {
        if($this->isHydrateMode() && ($entity = $this->getNormaliserService()->findCreated($euid))) {
            return $entity;
        }
        $class = Encoders::getClassOfEuid($euid);
        $repo = $this->em->getRepository($class);
        $entity = $repo->findOneBy(['euid' => $euid]);
        return $entity instanceof BaseEntityInterface ? $entity : null;
    }

    public function euidExists(
        string $euid,
        bool $getData = false
    ): bool|null|array
    {
        if($this->isHydrateMode() && ($entity = $this->getNormaliserService()->findCreated($euid))) {
            return true;
        }
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

    public function findByUname(
        string $uname
    ): ?BaseEntityInterface
    {
        if($this->isHydrateMode() && ($entity = $this->getNormaliserService()->findCreated($uname))) {
            return $entity;
        }
        $unameOjb = $this->getRepository(Uname::class)->find($uname);
        $entity = $unameOjb instanceof UnameInterface
            ? $this->findByEuid($unameOjb->getEntityEuid())
            : null;
        return $entity instanceof BaseEntityInterface ? $entity : null;
    }

    public function findUnameByUname(
        string $uname
    ): ?UnameInterface
    {
        if($this->isHydrateMode() && ($entity = $this->getNormaliserService()->findUnameCreated($uname))) {
            return $entity;
        }
        return $this->findById(Uname::class, $uname);
    }

    public function getEuidOfUname(
        string $uname
    ): ?string
    {
        if(Encoders::isUnameFormatValid($uname) || Encoders::isEuidFormatValid($uname)) {
            if($this->isHydrateMode()) {
                $unameOjb = $this->getNormaliserService()->findCreated($uname);
            }
            $unameOjb ??= $this->getRepository(Uname::class)->findOneById($uname);
            if($unameOjb instanceof UnameInterface) {
                $euid = $unameOjb->getEntityEuid();
                if(Encoders::isEuidFormatValid($euid)) return $euid;
            }
        }
        return null;
    }

    public function findByUniqueValue(
        string $value
    ): ?BaseEntityInterface {
        return Encoders::isEuidFormatValid($value)
            ? $this->findByEuid($value)
            : $this->findByUname($value);
    }

    public function getClassnameByUname(
        string $uname
    ): ?string
    {
        if($this->isHydrateMode()) {
            $entity = $this->getNormaliserService()->findCreated($uname);
            $result = $entity ? $entity->getClassname() : $this->getNormaliserService()->tryFindCatalogueClassname($uname);
            if($result) return $result;
        }
        return $this->getRepository(Uname::class)->getClassnameByUname($uname);
    }

    public function getClassnameByEuidOrUname(
        string $euidOrUname
    ): ?string
    {
        return Encoders::isEuidFormatValid($euidOrUname)
            ? Encoders::getClassOfEuid($euidOrUname)
            : $this->getClassnameByUname($euidOrUname);
    }


    /************************************************************************************************************/
    /** DATABASE REQUESTS                                                                                       */
    /************************************************************************************************************/

    public function count(
        string $classname,
        bool|array $criteria = []
    ): int {
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

    public function findAll(
        string $classname,
        bool|array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array
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

    public function findOneBy(
        string $classname,
        int|string $identifier,
        bool|array $criteria = [],
        ?array $orderBy = null,
    ): ?object
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

    public static function getCriteriaEnabled(
        string $classname
    ): array
    {
        return is_a($classname, TraitEnabledInterface::class, true) ? static::CRITERIA_ENABLED : [];
        // return is_a($classname, TraitEnabledInterface::class, true) ? [Criteria::expr()->eq('enabled', true)] : [];
    }

    public static function getCriteriaDisabled(
        string $classname
    ): array
    {
        return is_a($classname, TraitEnabledInterface::class, true) ? static::CRITERIA_DISABLED : [];
        // return is_a($classname, TraitEnabledInterface::class, true) ? [Criteria::expr()->eq('enabled', false)] : [];
    }


    /************************************************************************************************************/
    /** ENTITY INFO                                                                                             */
    /************************************************************************************************************/

    // /**
    //  * get class metadata
    //  * 
    //  * @see https://phpdox.net/demo/Symfony2/classes/Doctrine_ORM_Mapping_ClassMetadata.xhtml
    //  * @param string|object|null $objectOrClass
    //  * @return ClassMetadata|null
    //  */
    // public function getClassMetadata(
    //     null|string|object $objectOrClass = null,
    // ): ?ClassMetadata {
    //     if(empty($objectOrClass)) return null;
    //     if($objectOrClass instanceof BaseEntityInterface) {
    //         $objectOrClass = $objectOrClass->getClassname();
    //     }
    //     $classname = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;
    //     try {
    //         $cmd = $this->em->getClassMetadata($classname);
    //     } catch (Throwable $th) {
    //         $cmd = null;
    //     }
    //     return $cmd;
    // }

    public function addPostFlushInfos(PostFlushEventArgs $args): void
    {
        $this->postFlushInfos[] = $args->getObjectManager();
    }

    public function getPostFlushInfos(bool $getLastOnly = false): array
    {
        return $getLastOnly ? end($this->postFlushInfos) : $this->postFlushInfos;
    }

    // /**
    //  * is AppWire entity
    //  * - All entities are instance of BaseEntityInterface
    //  * 
    //  * @param string|object $objectOrClass
    //  * @return bool
    //  */
    // public static function isAppWireEntity(
    //     string|object $objectOrClass
    // ): bool {
    //     return is_string($objectOrClass)
    //         ? is_a($objectOrClass, BaseEntityInterface::class, true)
    //         : $objectOrClass instanceof BaseEntityInterface;
    // }

    // public static function isBetweenEntity(
    //     string|object $objectOrClass
    // ): bool
    // {
    //     return is_string($objectOrClass)
    //         ? is_a($objectOrClass, BetweenManyInterface::class, true)
    //         : $objectOrClass instanceof BetweenManyInterface;
    // }

    // public static function isTranslationEntity(
    //     string|object $objectOrClass
    // ): bool
    // {
    //     return is_string($objectOrClass)
    //         ? is_a($objectOrClass, WireTranslationInterface::class, true)
    //         : $objectOrClass instanceof WireTranslationInterface;
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


    // /**
    //  * get entity names
    //  * 
    //  * @param bool $asShortnames
    //  * @param bool $allnamespaces
    //  * @param bool $onlyInstantiables
    //  * @return array
    //  */
    // public function getEntityNames(
    //     bool $asShortnames = false,
    //     bool $allnamespaces = false,
    //     bool $onlyInstantiables = false,
    // ): array
    // {
    //     return $onlyInstantiables
    //         ? $this->getEntitiesMetadata()->enableShortnames($asShortnames)->enableFilterAppWire(!$allnamespaces)->findFinals(null)
    //         : $this->getEntitiesMetadata()->enableShortnames($asShortnames)->enableFilterAppWire(!$allnamespaces)->findAll(null)
    //         ;
    // }

    // /**
    //  * Get App entity names
    //  * 
    //  * @param bool $asShortnames
    //  * @param bool $onlyInstantiables
    //  * @return array
    //  */
    // public function getAppEntityNames(
    //     bool $asShortnames = false,
    //     bool $onlyInstantiables = false
    // ): array
    // {
    //     return $this->getEntitiesMetadata()->enableShortnames($asShortnames)->enableFilterAppWire(true)->findFinals(null);
    // }

    // public function getBetweenEntityNames(
    //     bool $asShortnames = false
    // ): array
    // {
    //     return $this->getEntitiesMetadata()->enableShortnames($asShortnames)->enableFilterAppWire(false)->findAll(BetweenManyInterface::class);
    // }

    // public function getTranslationEntityNames(
    //     bool $asShortnames = false
    // ): array
    // {
    //     return $this->getEntitiesMetadata()->enableShortnames($asShortnames)->enableFilterAppWire(false)->findAll(WireTranslationInterface::class);
    // }

    // public function getFinalEntities(
    //     bool $asShortnames = false,
    //     bool $allnamespaces = false,
    // ): array
    // {
    //     return $this->getEntitiesMetadata()->enableShortnames($asShortnames)->enableFilterAppWire(!$allnamespaces)->findFinals(null);
    // }

    // public function resolveFinalEntitiesByNames(
    //     string|array $interfaces,
    //     bool $allnamespaces = false
    // ): array
    // {
    //     return $this->getEntitiesMetadata()->enableFilterAppWire(!$allnamespaces)->findFinals($interfaces);
    //     // $classes = $this->getFinalEntities(false, $allnamespaces);
    //     // return Objects::filterByInterface($interfaces, $classes, true);
    // }

    // public function resolveFinalEntity(
    //     string|array $interfaces,
    //     bool $allnamespaces = false
    // ): ?string
    // {
    //     return $this->getEntitiesMetadata()->enableFilterAppWire(!$allnamespaces)->findOneFinalOrNull($interfaces);
    // }


    // /**
    //  * entity exists
    //  * 
    //  * @param string $classname
    //  * @param bool $allnamespaces
    //  * @param bool $onlyInstantiables
    //  * @return bool
    //  */
    // public function entityExists(
    //     string $classname, // --> or shortname
    //     bool $allnamespaces = true,
    //     bool $onlyInstantiables = false,
    // ): bool {
    //     $list = $this->getEntityNames(true, $allnamespaces, $onlyInstantiables);
    //     return in_array($classname, $list) || array_key_exists($classname, $list);
    // }

    // public function getClassnameByShortname(
    //     string $shortname,
    //     bool $allnamespaces = false,
    //     bool $onlyInstantiables = false
    // ): ?string {
    //     $list = $this->getEntityNames(true, $allnamespaces, $onlyInstantiables);
    //     return array_search($shortname, $list) ?: null;
    // }

    // /**
    //  * get fieds names of entity with unique constraint
    //  * 
    //  * @param string $classname
    //  * @param bool|null $flatlisted
    //  * @return string
    //  */
    // public static function getConstraintUniqueFields(
    //     string $classname,
    //     bool|null $flatlisted = false
    // ): array {
    //     $uniqueFields = [
    //         'hierar' => [],
    //         'flatlist' => [],
    //     ];
    //     throw new Exception('Not implemented yet! Please rewrite with use of ClassMetadata!');
    //     // foreach (Objects::getClassAttributes($classname, UniqueEntity::class, true) as $attr) {
    //     //     /** @var UniqueEntity $attr */
    //     //     $ufields = (array)$attr->fields;
    //     //     if (isset($ufields)) {
    //     //         $uniqueFields['hierar'][] = $ufields;
    //     //         $uniqueFields['flatlist'] = array_unique(array_merge($uniqueFields['flatlist'], $ufields));
    //     //     }
    //     // }
    //     // if (is_null($flatlisted)) return $uniqueFields;
    //     // return $flatlisted
    //     //     ? $uniqueFields['flatlist']
    //     //     : $uniqueFields['hierar'];
    // }

    // /**
    //  * Get Doctrine relations of entity
    //  * 
    //  * @param string|BaseEntityInterface $objectOrClass
    //  * @param null|Closure $filter
    //  * @param boolean $excludeSelf
    //  * @return array
    //  */
    // public function getRelateds(
    //     string|BaseEntityInterface $objectOrClass,
    //     ?Closure $filter = null,
    //     bool $excludeSelf = false
    // ): array
    // {
    //     $classname = $objectOrClass instanceof ClassDescriptionInterface ? $objectOrClass->getClassname() : $objectOrClass;
    //     $classnames = [];
    //     foreach ($this->getEntityNames(false, false, true) as $class) {
    //         if (!($excludeSelf && is_a($class, $classname, true))) {
    //             $cmd = $this->getClassMetadata($class);
    //             foreach ($cmd->associationMappings as $mapping) {
    //                 if(is_a($mapping->targetEntity, $classname, true) && (is_callable($filter) ? $filter($mapping, $cmd) : true)) {
    //                     $classnames[$class] ??= [];
    //                     $classnames[$class][] = $mapping;
    //                 }
    //             }
    //         }
    //     }
    //     return $classnames;
    // }


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
    public function getBrowserPath(
        WireImageInterface|WirePdfInterface $media,
        ?string $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): ?string {
        $browserPath = $this->vichHelper->asset($media);
        if ($filter && !($media instanceof WirePdfInterface)) {
            $browserPath = $this->liipCache->getBrowserPath($browserPath, $filter, $runtimeConfig, $resolver, $referenceType);
        }
        return $browserPath;
    }


}
