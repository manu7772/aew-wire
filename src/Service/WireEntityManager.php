<?php
namespace Aequation\WireBundle\Service;

// Aequation

use Aequation\WireBundle\Attribute\CacheManaged;
use Aequation\WireBundle\Attribute\PostEmbeded;
use Aequation\WireBundle\Component\EntityContainer;
use Aequation\WireBundle\Component\EntityEmbededStatus;
use Aequation\WireBundle\Component\EntitySelfState;
use Aequation\WireBundle\Component\interface\EntityContainerInterface;
use Aequation\WireBundle\Component\interface\EntitySelfStateInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\BetweenManyInterface;
use Aequation\WireBundle\Entity\interface\TraitOwnerInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Entity\BaseMappSuperClassEntity;
use Aequation\WireBundle\Entity\interface\TraitDatetimedInterface;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Entity\interface\TraitWebpageableInterface;
use Aequation\WireBundle\Entity\interface\WireLanguageInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\CacheServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\SurveyRecursionInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\HttpRequest;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Psr\Log\LoggerInterface;
// PHP
use Exception;
use Closure;
use ReflectionMethod;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

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

    protected NormalizerServiceInterface $normalizer;
    protected readonly UnitOfWork $uow;
    public int $debug_mode = 0;
    protected array $postFlushInfos = [];
    protected array $relatedDependencies = [];

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
        return $this->normalizer ??= $this->appWire->get(NormalizerServiceInterface::class);
    }


    /****************************************************************************************************/
    /** DEBUG MODE                                                                                      */
    /****************************************************************************************************/

    public function isDebugMode(): bool
    {
        $is = $this->debug_mode > 0 || HttpRequest::isCli();
        // if($is) {
        //     // Is debug mode, so
        //     foreach ($this->getUnitOfWork()->getIdentityMap() as $oid => $value) {
        //         # code...
        //     }
        // }
        return $is;
    }
    
    public function incDebugMode(): bool
    {
        $this->debug_mode++;
        return $this->isDebugMode();
    }

    public function decDebugMode(): bool
    {
        $this->debug_mode--;
        return $this->isDebugMode();
    }

    public function resetDebugMode(): bool
    {
        $this->debug_mode = 0;
        return $this->isDebugMode();
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
    ): ?WireEntityServiceInterface {
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

    // public function insertEmbededStatus(
    //     BaseEntityInterface $entity
    // ): void {
    //     if (!$entity->hasEmbededStatus()) {
    //         new EntityEmbededStatus($entity, $this->appWire);
    //         // Apply PostEmbeded events
    //         $isNew = $entity->getSelfState()->isNew();
    //         $attributes = Objects::getMethodAttributes($entity, PostEmbeded::class, ReflectionMethod::IS_PUBLIC);
    //         foreach ($attributes as $instances) {
    //             $instance = reset($instances);
    //             if ($isNew && $instance->isOnCreate()) {
    //                 $entity->{$instance->getMethodName()}();
    //             } else if($instance->isOnLoad()) {
    //                 $entity->{$instance->getMethodName()}();
    //             }
    //         }
    //     }
    // }

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
        array $context = [],
        bool $tryService = true
    ): BaseEntityInterface {
        $this->surveyRecursion->survey(__METHOD__.'::'.$classname);
        if($tryService && $service = $this->getEntityService($classname)) {
            return $service->createEntity($data, $context);
        }
        if(!class_exists($classname)) {
            throw new Exception(vsprintf('Error %s line %d: class %s not found!', [__METHOD__, __LINE__, $classname]));
        }
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

    // if($entity instanceof WireMenuInterface) dump($index.' => '.$entity->getName().' => U:'.$entity->getUnameName().' / Model: '.($entity->getSelfState()->isModel() ? 'true' : 'false'));

    /**
     * create model
     * 
     * @return BaseEntityInterface
     */
    public function createModel(
        string $classname,
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = [],
        bool $tryService = true
    ): BaseEntityInterface {
        $this->surveyRecursion->survey(__METHOD__.'::'.$classname);
        if($tryService && $service = $this->getEntityService($classname)) {
            return $service->createModel($data, $context);
        }
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
        ?array $context = [],
        bool $tryService = true
    ): BaseEntityInterface|false {
        throw new Exception('Not implemented yet!');

        $this->surveyRecursion->survey(__METHOD__.'::'.$entity->getClassname());
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
            if($this->appWire->isDev()) {
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
            if($this->appWire->isDev()) {
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
                        // TraitOwnerInterface
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
                                } else if ($this->appWire->isDev()) {
                                    throw new Exception(vsprintf('Error %s line %d: entity %s %s has no owner!', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
                                }
                            }
                        }
                        break;
                    case TraitWebpageableInterface::class:
                        // TraitWebpageableInterface
                        /** @var TraitWebpageableInterface $entity */
                        $unames = [
                            'User' => 'wp_user_presentation',
                        ];
                        $uname = $unames[$entity->getShortname()] ?? null;
                        if($uname && empty($entity->getWebpage()) && ($webpage = $this->findEntityByUname($uname))) {
                            if($webpage->getEmbededStatus()->isContained()) $entity->setWebpage($webpage);
                        }
                        break;
                    case TraitDatetimedInterface::class:
                        // TraitDatetimedInterface
                        /** @var TraitDatetimedInterface $entity */
                        if(empty($entity->getLanguage())) {
                            if($defaultLanguage = $this->appWire->getCurrentLanguage()) {
                                $entity->setLanguage($defaultLanguage);
                                // Default timezone setted automatically
                            } else if($this->isDebugMode()) {
                                foreach ($this->getNormaliserService()->getCreateds() as $ent) {
                                    if($ent instanceof WireLanguageInterface && $ent->isPrefered()) {
                                        $entity->setLanguage($ent);
                                    }
                                }
                            }
                        }
                        break;
                    case TraitUnamedInterface::class:
                        // TraitUnamedInterface
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
    /** REPOSITORY / QUERYS                                                                             */
    /****************************************************************************************************/

    public function getRepository(string|BaseEntityInterface $objectOrClass): ?EntityRepository
    {
        $classname = $objectOrClass instanceof BaseEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        return $classname
            ? $this->em->getRepository($classname)
            : null;
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
    //     if($this->appWire->isDev() && !($repo instanceof BaseWireRepositoryInterface)) {
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
    public function findEntityById(
        string $classname,
        string $id
    ): ?BaseEntityInterface
    {
        $repo = $this->em->getRepository($classname);
        return $repo->find($id);
    }

    public function findEntityByEuid(
        string $euid
    ): ?BaseEntityInterface
    {
        if($this->isDebugMode() && ($entity = $this->getNormaliserService()->findCreated($euid))) {
            return $entity;
        }
        $class = Encoders::getClassOfEuid($euid);
        $repo = $this->em->getRepository($class);
        $entity = $repo->findOneBy(['euid' => $euid]);
        return $entity instanceof BaseEntityInterface ? $entity : null;
    }

    public function entityWithEuidExists(
        string $euid,
        bool $getData = false
    ): bool|null|array
    {
        if($this->isDebugMode() && ($entity = $this->getNormaliserService()->findCreated($euid))) {
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

    public function findEntityByUname(
        string $uname
    ): ?BaseEntityInterface
    {
        if($this->isDebugMode() && ($entity = $this->getNormaliserService()->findCreated($uname))) {
            return $entity;
        }
        $unameOjb = $this->getRepository(Uname::class)->find($uname);
        $entity = $unameOjb instanceof UnameInterface
            ? $this->findEntityByEuid($unameOjb->getEntityEuid())
            : null;
        return $entity instanceof BaseEntityInterface ? $entity : null;
    }

    public function findUnameByUname(
        string $uname
    ): ?UnameInterface
    {
        if($this->isDebugMode() && ($entity = $this->getNormaliserService()->findUnameCreated($uname))) {
            return $entity;
        }
        return $this->findEntityById(Uname::class, $uname);
    }

    public function getEuidOfUname(
        string $uname
    ): ?string
    {
        if(Encoders::isUnameFormatValid($uname) || Encoders::isEuidFormatValid($uname)) {
            if($this->isDebugMode()) {
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

    public function findEntityByUniqueValue(
        string $value
    ): ?BaseEntityInterface {
        return Encoders::isEuidFormatValid($value)
            ? $this->findEntityByEuid($value)
            : $this->findEntityByUname($value);
    }

    public function getClassnameByUname(
        string $uname
    ): ?string
    {
        if($this->isDebugMode()) {
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

    public function getEntitiesCount(
        string $classname,
        bool|array $criteria = []
    ): int {
        if($service = $this->getEntityService($classname)) {
            return $service->getCount($criteria);
        }
        if(is_bool($criteria)) {
            $criteria = true === $criteria ? static::getCriteriaEnabled($classname) : static::getCriteriaDisabled($classname);
        }
        /** @var BaseWireRepositoryInterface */
        $repo = $this->em->getRepository($classname);
        return $repo->count($criteria);
    }

    public function findAllEntities(
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

    public function findEntity(
        string $classname,
        int|string $identifier,
        bool|array $criteria = [],
        ?array $orderBy = null,
    ): ?object
    {
        if($service = $this->getEntityService($classname)) {
            return $service->find($identifier, $criteria, $orderBy);
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

    /**
     * get class metadata
     * 
     * @see https://phpdox.net/demo/Symfony2/classes/Doctrine_ORM_Mapping_ClassMetadata.xhtml
     * @param string|object|null $objectOrClass
     * @return ClassMetadata|null
     */
    public function getClassMetadata(
        null|string|object $objectOrClass = null,
    ): ?ClassMetadata {
        if(empty($objectOrClass)) return null;
        if($objectOrClass instanceof BaseEntityInterface) {
            $objectOrClass = $objectOrClass->getClassname();
        }
        $classname = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;
        try {
            $cmd = $this->em->getClassMetadata($classname);
        } catch (Throwable $th) {
            $cmd = null;
        }
        return $cmd;
    }

    public function addPostFlushInfos(PostFlushEventArgs $args): void
    {
        $this->postFlushInfos[] = $args->getObjectManager();
    }

    public function getPostFlushInfos(bool $getLastOnly = false): array
    {
        return $getLastOnly ? end($this->postFlushInfos) : $this->postFlushInfos;
    }

    /**
     * is AppWire entity
     * - All entities are instance of BaseEntityInterface
     * 
     * @param string|object $objectOrClass
     * @return bool
     */
    public static function isAppWireEntity(
        string|object $objectOrClass
    ): bool {
        return is_string($objectOrClass)
            ? is_a($objectOrClass, BaseEntityInterface::class, true)
            : $objectOrClass instanceof BaseEntityInterface;
    }

    public static function isBetweenEntity(
        string|object $objectOrClass
    ): bool
    {
        return is_string($objectOrClass)
            ? is_a($objectOrClass, BetweenManyInterface::class, true)
            : $objectOrClass instanceof BetweenManyInterface;
    }

    public static function isTranslationEntity(
        string|object $objectOrClass
    ): bool
    {
        return is_string($objectOrClass)
            ? is_a($objectOrClass, WireTranslationInterface::class, true)
            : $objectOrClass instanceof WireTranslationInterface;
    }

    /**
     * get entity names
     * 
     * @param bool $asShortnames
     * @param bool $allnamespaces
     * @param bool $onlyInstantiables
     * @return array
     */
    #[CacheManaged(name: 'entities_names', params: ['asShortnames' => false, 'allnamespaces' => false, 'onlyInstantiables' => false, ])]
    #[CacheManaged(name: 'entities_shortnames', params: ['asShortnames' => true, 'allnamespaces' => false, 'onlyInstantiables' => false, ])]
    #[CacheManaged(name: 'entities_names_instantiables', params: ['asShortnames' => false, 'allnamespaces' => false, 'onlyInstantiables' => true, ])]
    #[CacheManaged(name: 'entities_shortnames_instantiables', params: ['asShortnames' => true, 'allnamespaces' => false, 'onlyInstantiables' => true, ])]
    #[CacheManaged(name: 'entities_all_names', params: ['asShortnames' => false, 'allnamespaces' => false, 'onlyInstantiables' => false, ])]
    #[CacheManaged(name: 'entities_all_shortnames', params: ['asShortnames' => true, 'allnamespaces' => true, 'onlyInstantiables' => false, ])]
    #[CacheManaged(name: 'entities_all_names_instantiables', params: ['asShortnames' => false, 'allnamespaces' => true, 'onlyInstantiables' => true, ])]
    #[CacheManaged(name: 'entities_all_shortnames_instantiables', params: ['asShortnames' => true, 'allnamespaces' => true, 'onlyInstantiables' => true, ])]
    public function getEntityNames(
        bool $asShortnames = false,
        bool $allnamespaces = false,
        bool $onlyInstantiables = false,
    ): array
    {
        $callback = function() use ($asShortnames, $allnamespaces, $onlyInstantiables): array
        {
            $names = [];
            // $this->em->getConfiguration()->getEntityNamespaces() as $classname --> or
            foreach ($this->em->getMetadataFactory()->getAllMetadata() as $cmd) {
                /** @var ClassMetadata $cmd */
                if (!$onlyInstantiables || ($cmd->reflClass->isInstantiable() && !$cmd->isMappedSuperclass && count($cmd->subClasses) === 0)) {
                    if ($allnamespaces || static::isAppWireEntity($cmd->name)) {
                        $names[$cmd->name] = $asShortnames
                            ? $cmd->reflClass->getShortname()
                            : $cmd->name;
                    }
                }
            }
            return $names;
        };
        // Use CacheService
        $test = implode('|', [$asShortnames, $allnamespaces, $onlyInstantiables]);
        switch ($test) {
            case '||': return $this->cacheService->get('entities_names', $callback); break;
            case '1||': return $this->cacheService->get('entities_shortnames', $callback); break;
            case '||1': return $this->cacheService->get('entities_names_instantiables', $callback); break;
            case '1||1': return $this->cacheService->get('entities_shortnames_instantiables', $callback); break;
            case '|1|': return $this->cacheService->get('entities_all_names', $callback); break;
            case '1|1|': return $this->cacheService->get('entities_all_shortnames', $callback); break;
            case '|1|1': return $this->cacheService->get('entities_all_names_instantiables', $callback); break;
            case '1|1|1': return $this->cacheService->get('entities_all_shortnames_instantiables', $callback); break;
            default:
                if($this->appWire->isDev()) {
                    throw new Exception(vsprintf('Error %s line %d: invalid cache name "%s" for getAppEntityNames', [__METHOD__, __LINE__, $test]));
                }
                return $callback();
                break;
        }
    }

    /**
     * Get App entity names
     * 
     * @param bool $asShortnames
     * @param bool $onlyInstantiables
     * @return array
     */
    #[CacheManaged(name: 'app_entities_names', params: ['asShortnames' => false, 'onlyInstantiables' => false, 'commentaire' => 'Get entity names'])]
    #[CacheManaged(name: 'app_entities_shortnames', params: ['asShortnames' => true, 'onlyInstantiables' => false, 'commentaire' => 'Get entity shortnames'])]
    #[CacheManaged(name: 'app_entities_names_instantiables', params: ['asShortnames' => false, 'onlyInstantiables' => true, 'commentaire' => 'Get instantiable entity names'])]
    #[CacheManaged(name: 'app_entities_shortnames_instantiables', params: ['asShortnames' => true, 'onlyInstantiables' => true, 'commentaire' => 'Get instantiable entity shortnames'])]
    public function getAppEntityNames(
        bool $asShortnames = false,
        bool $onlyInstantiables = false
    ): array
    {
        $callback = function() use ($asShortnames, $onlyInstantiables): array
        {
            $names = $this->getEntityNames($asShortnames, true, $onlyInstantiables);
            return array_filter(
                $names,
                fn($name) => static::isAppWireEntity($name),
                ARRAY_FILTER_USE_KEY
            );
        };
        // Use CacheService
        $test = implode('|', [$asShortnames, $onlyInstantiables]);
        switch ($test) {
            case '|': return $this->cacheService->get('app_entities_names', $callback); break;
            case '1|': return $this->cacheService->get('app_entities_shortnames', $callback); break;
            case '|1': return $this->cacheService->get('app_entities_names_instantiables', $callback); break;
            case '1|1': return $this->cacheService->get('app_entities_shortnames_instantiables', $callback); break;
            default:
                if($this->appWire->isDev()) {
                    throw new Exception(vsprintf('Error %s line %d: invalid cache name "%s" for getAppEntityNames', [__METHOD__, __LINE__, $test]));
                }
                return $callback();
                break;
        }
    }

    #[CacheManaged(name: 'between_entities_names', params: ['asShortnames' => false])]
    #[CacheManaged(name: 'between_entities_shortnames', params: ['asShortnames' => true])]
    public function getBetweenEntityNames(
        bool $asShortnames = false
    ): array
    {
        $callback = function() use ($asShortnames): array
        {
            $names = [];
            foreach ($this->em->getMetadataFactory()->getAllMetadata() as $cmd) {
                if(static::isBetweenEntity($cmd->name) && $cmd->reflClass->isInstantiable() && !$cmd->isMappedSuperclass && count($cmd->subClasses) === 0) {
                    $names[$cmd->name] = $asShortnames
                        ? $cmd->reflClass->getShortname()
                        : $cmd->name;
                }
            }
            return $names;
        };
        // Use CacheService
        return $asShortnames
            ? $this->cacheService->get('between_entities_shortnames', $callback)
            : $this->cacheService->get('between_entities_names', $callback);
    }

    #[CacheManaged(name: 'translation_entities_names', params: ['asShortnames' => false])]
    #[CacheManaged(name: 'translation_entities_shortnames', params: ['asShortnames' => true])]
    public function getTranslationEntityNames(
        bool $asShortnames = false
    ): array
    {
        $callback = function() use ($asShortnames): array
        {
            $names = [];
            foreach ($this->em->getMetadataFactory()->getAllMetadata() as $cmd) {
                if(static::isTranslationEntity($cmd->name) && $cmd->reflClass->isInstantiable() && !$cmd->isMappedSuperclass && count($cmd->subClasses) === 0) {
                    $names[$cmd->name] = $asShortnames
                    ? $cmd->reflClass->getShortname()
                    : $cmd->name;
                }
            }
            return $names;
        };
        // Use CacheService
        return $asShortnames
            ? $this->cacheService->get('translation_entities_shortnames', $callback)
            : $this->cacheService->get('translation_entities_names', $callback);
    }

    #[CacheManaged(name: 'final_entities_names', params: ['asShortnames' => false, 'allnamespaces' => false, 'commentaire' => 'Get entity names'])]
    #[CacheManaged(name: 'final_entities_shortnames', params: ['asShortnames' => true, 'allnamespaces' => false, 'commentaire' => 'Get entity shortnames'])]
    #[CacheManaged(name: 'final_all_entities_names', params: ['asShortnames' => false, 'allnamespaces' => true, 'commentaire' => 'Get instantiable entity names'])]
    #[CacheManaged(name: 'final_all_entities_shortnames', params: ['asShortnames' => true, 'allnamespaces' => true, 'commentaire' => 'Get instantiable entity shortnames'])]
    public function getFinalEntities(
        bool $asShortnames = false,
        bool $allnamespaces = false,
    ): array
    {
        $callback = function() use ($asShortnames, $allnamespaces): array
        {
            $names = [];
            foreach ($this->getEntityNames($asShortnames, $allnamespaces, false) as $name => $shortname) {
                $cmd = $this->getClassMetadata($name);
                if (count($cmd->subClasses) === 0 && !$cmd->isMappedSuperclass) {
                    $names[$name] = $shortname;
                }
            }
            return $names;
        };
        // Use CacheService
        $test = implode('|', [$asShortnames, $allnamespaces]);
        switch ($test) {
            case '|': return $this->cacheService->get('final_entities_names', $callback); break;
            case '1|': return $this->cacheService->get('final_entities_shortnames', $callback); break;
            case '|1': return $this->cacheService->get('final_all_entities_names', $callback); break;
            case '1|1': return $this->cacheService->get('final_all_entities_shortnames', $callback); break;
            default:
                if($this->appWire->isDev()) {
                    throw new Exception(vsprintf('Error %s line %d: invalid cache name "%s" for getAppEntityNames', [__METHOD__, __LINE__, $test]));
                }
                return $callback();
                break;
        }
    }

    public function resolveFinalEntitiesByNames(
        string|array $interfaces,
        bool $allnamespaces = false
    ): array
    {
        $classes = $this->getFinalEntities(false, $allnamespaces);
        return Objects::filterByInterface($interfaces, $classes, true);
    }

    /**
     * entity exists
     * 
     * @param string $classname
     * @param bool $allnamespaces
     * @param bool $onlyInstantiables
     * @return bool
     */
    public function entityExists(
        string $classname, // --> or shortname
        bool $allnamespaces = true,
        bool $onlyInstantiables = false,
    ): bool {
        $list = $this->getEntityNames(true, $allnamespaces, $onlyInstantiables);
        return in_array($classname, $list) || array_key_exists($classname, $list);
    }

    public function getClassnameByShortname(
        string $shortname,
        bool $allnamespaces = false,
        bool $onlyInstantiables = false
    ): ?string {
        $list = $this->getEntityNames(true, $allnamespaces, $onlyInstantiables);
        return array_search($shortname, $list) ?: null;
    }

    /**
     * get fieds names of entity with unique constraint
     * 
     * @param string $classname
     * @param bool|null $flatlisted
     * @return string
     */
    public static function getConstraintUniqueFields(
        string $classname,
        bool|null $flatlisted = false
    ): array {
        $uniqueFields = [
            'hierar' => [],
            'flatlist' => [],
        ];
        throw new Exception('Not implemented yet! Please rewrite with use of ClassMetadata!');
        // foreach (Objects::getClassAttributes($classname, UniqueEntity::class, true) as $attr) {
        //     /** @var UniqueEntity $attr */
        //     $ufields = (array)$attr->fields;
        //     if (isset($ufields)) {
        //         $uniqueFields['hierar'][] = $ufields;
        //         $uniqueFields['flatlist'] = array_unique(array_merge($uniqueFields['flatlist'], $ufields));
        //     }
        // }
        // if (is_null($flatlisted)) return $uniqueFields;
        // return $flatlisted
        //     ? $uniqueFields['flatlist']
        //     : $uniqueFields['hierar'];
    }

    /**
     * Get Doctrine relations of entity
     * 
     * @param string|BaseEntityInterface $objectOrClass
     * @param null|Closure $filter
     * @param boolean $excludeSelf
     * @return array
     */
    public function getRelateds(
        string|BaseEntityInterface $objectOrClass,
        ?Closure $filter = null,
        bool $excludeSelf = false
    ): array
    {
        $classname = $objectOrClass instanceof BaseEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        $classnames = [];
        foreach ($this->getEntityNames(false, false, true) as $class) {
            if (!($excludeSelf && is_a($class, $classname, true))) {
                $cmd = $this->getClassMetadata($class);
                foreach ($cmd->associationMappings as $mapping) {
                    if(is_a($mapping->targetEntity, $classname, true) && (is_callable($filter) ? $filter($mapping, $cmd) : true)) {
                        $classnames[$class] ??= [];
                        $classnames[$class][] = $mapping;
                    }
                }
            }
        }
        return $classnames;
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
