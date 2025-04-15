<?php
namespace Aequation\WireBundle\Service;

// Aequation

use Aequation\WireBundle\Attribute\CacheManaged;
use Aequation\WireBundle\Attribute\PostEmbeded;
use Aequation\WireBundle\Attribute\SerializationMapping;
use Aequation\WireBundle\Component\EntityEmbededStatus;
use Aequation\WireBundle\Component\NormalizeDataContainer;
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
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\CacheServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\HttpRequest;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
// PHP
use Exception;
use Closure;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\AssociationMapping;
use ReflectionMethod;

/**
 * Class WireEntityManager
 * @package Aequation\WireBundle\Service
 */
#[AsAlias(WireEntityManagerInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class WireEntityManager implements WireEntityManagerInterface
{
    use TraitBaseService;

    public const MAX_SURVEY_RECURSION = 300;
    public const SERIALIZATION_MAPPINGS_BY_ATTRIBUTE = true;
    private array $__src = [];
    // Criteria
    public const CRITERIA_ENABLED = ['enabled' => true];
    public const CRITERIA_DISABLED = ['enabled' => false];

    protected ArrayCollection $createds;
    protected NormalizerServiceInterface $normalizer;
    protected readonly UnitOfWork $uow;
    public int $debug_mode = 0;
    public bool $dismissCreateds = false;
    protected array $postFlushInfos = [];

    /**
     * constructor.
     * 
     * @param EntityManagerInterface $em
     * @param AppWireServiceInterface $appWire
     * @param UploaderHelper $vichHelper
     * @param CacheManager $liipCache
     */
    public function __construct(
        public readonly EntityManagerInterface $em,
        public readonly AppWireServiceInterface $appWire,
        public readonly CacheServiceInterface $cacheService,
        protected UploaderHelper $vichHelper,
        protected CacheManager $liipCache
    ) {
        // $this->uow = $this->em->getUnitOfWork();
        $this->createds = new ArrayCollection();
        // $this->debug_mode = HttpRequest::isCli();
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
        return $this->debug_mode > 0 || HttpRequest::isCli();
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


    /****************************************************************************************************/
    /** CREATED                                                                                         */
    /****************************************************************************************************/

    public function dismissCreateds(bool $dismissCreateds): void
    {
        $this->dismissCreateds = $dismissCreateds;
    }

    public function isDismissCreateds(): bool
    {
        return $this->dismissCreateds;
    }

    public function addCreated(BaseEntityInterface $entity): void
    {
        $index = spl_object_hash($entity);
        if (!$this->createds->containsKey($index)) {
            $this->createds->set($index, $entity);
        } else if ($this->appWire->isDev()) {
            $exists = $this->createds->get($index);
            throw new Exception(vsprintf('Error %s line %d: entity with %s already exists!%s- 1 - %s %s%s- 2 - %s %s', [__METHOD__, __LINE__, $index, PHP_EOL, $entity->getClassname(), $entity->__toString(), PHP_EOL, $exists->getClassname(), $exists->__toString()]));
        }
    }

    public function hasCreated(BaseEntityInterface $entity): bool
    {
        return $this->isDismissCreateds()
            ? false 
            : $this->createds->containsKey(spl_object_hash($entity));
    }

    public function clearCreateds(): bool
    {
        foreach ($this->createds as $key => $entity) {
            /** @var BaseEntityInterface $entity */
            $this->createds->removeElement($entity);
            $entity->getSelfState()->setDetached();
            // unset($entity);
        }
        // $this->createds->clear();
        return $this->createds->isEmpty();
    }

    /**
     * remove entity from persisted entities
     * Returns true if createds list is empty
     * 
     * @param BaseEntityInterface $entity
     * @return bool
     */
    public function clearPersisteds(): bool
    {
        $this->createds = $this->createds->filter(fn($entity) => !$entity->__estatus->isContained());
        return $this->createds->isEmpty();
    }

    public function findCreated(
        string $euidOrUname
    ): ?BaseEntityInterface {
        if($this->isDismissCreateds()) return null;
        foreach ($this->createds as $entity) {
            /** @var BaseEntityInterface $entity */
            if (
                ($entity instanceof BaseEntityInterface && $entity->getEuid() === $euidOrUname)
                || ($entity instanceof TraitUnamedInterface && $entity->getUname()->getUname() === $euidOrUname)
            ) {
                return $entity;
            }
        }
        return null;
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

    public function insertEmbededStatus(
        BaseEntityInterface $entity
    ): void {
        if (!$entity->hasEmbededStatus()) {
            new EntityEmbededStatus($entity, $this->appWire);
            // Apply PostEmbeded events
            $isNew = $entity->getSelfState()->isNew();
            $attributes = Objects::getMethodAttributes($entity, PostEmbeded::class, ReflectionMethod::IS_PUBLIC);
            foreach ($attributes as $instances) {
                $instance = reset($instances);
                if ($isNew && $instance->isOnCreate()) {
                    $entity->{$instance->getMethodName()}();
                } else if($instance->isOnLoad()) {
                    $entity->{$instance->getMethodName()}();
                }
            }
        }
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
        array $context = [],
        bool $tryService = true
    ): BaseEntityInterface {
        $this->surveyRecursion(__METHOD__.'::'.$classname);
        if($tryService && $service = $this->getEntityService($classname)) {
            return $service->createEntity($data, $context);
        }
        if(!$data || empty($data)) {
            $entity = new $classname();
            $this->postCreated($entity);
        } else {
            // Denormalize
            $context[NormalizeDataContainer::CONTEXT_CREATE_ONLY] = false;
            $context[NormalizeDataContainer::CONTEXT_AS_MODEL] = false;
            $normalizeContainer = new NormalizeDataContainer($this->getNormaliserService(), $classname, $data, $context);
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
        array $context = [],
        bool $tryService = true
    ): BaseEntityInterface {
        $this->surveyRecursion(__METHOD__.'::'.$classname);
        if($tryService && $service = $this->getEntityService($classname)) {
            return $service->createModel($data, $context);
        }
        if(!$data || empty($data)) {
            $model = new $classname();
            $model->getSelfState()->setModel();
            $this->postCreated($model);
        } else {
            // Denormalize
            $context[NormalizeDataContainer::CONTEXT_CREATE_ONLY] = true;
            $context[NormalizeDataContainer::CONTEXT_AS_MODEL] = true;
            $normalizeContainer = new NormalizeDataContainer($this->getNormaliserService(), $classname, $data, $context);
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

        $this->surveyRecursion(__METHOD__.'::'.$entity->getClassname());
        // if($tryService && $service = $this->getEntityService($entity)) {
        //     return $service->createClone($entity, $changes, $context);
        // }
        // $classname = $entity->getClassname();
        // $normalizeContainer = new NormalizeDataContainer(true, false, $context, 'clone');
        // $data = $this->getNormaliserService()->normalizeEntity($entity, null, $normalizeContainer->getContext());
        // // Create clone
        // // $normalizeContainer = new NormalizeDataContainer(true, false, $context, 'clone');
        // $clone = $this->createEntity($classname, array_merge($data, $changes), $normalizeContainer->getContext());
        // // Add some stuff here...
        // return $clone;
    }


    /****************************************************************************************************/
    /** ENTITY EVENTS                                                                                   */
    /****************************************************************************************************/

    /**
     * After a entity is loaded from database, add EntityEmbededStatus and more actions...
     * 
     * @param BaseEntityInterface $entity
     * @return void
     */
    public function postLoaded(
        BaseEntityInterface $entity
    ): void {
        if($entity->getSelfState() && $entity->getSelfState()?->isPostLoaded() ?? false) return;
        $entity->doInitializeSelfState('loaded', 'auto');
        $entity->getSelfState()->setPostLoaded();
        // Add EntityEmbededStatus is necessary
        /** @var BaseEntityInterface&BaseMappSuperClassEntity $entity */
        if($this->isDebugMode() || in_array(Events::postLoad, $entity::DO_EMBED_STATUS_EVENTS)) {
            $this->insertEmbededStatus($entity);
        }
    }

    /**
     * After a new entity created, add it to createds list and more actions...
     * 
     * @param BaseEntityInterface $entity
     * @return void
     */
    public function postCreated(
        BaseEntityInterface $entity
    ): void {
        if($entity->getSelfState()?->isPostCreated() ?? false) return;
        $this->insertEmbededStatus($entity);
        if ($entity->getSelfState()->isEntity()) {
            // Save entity
            if (!$this->hasCreated($entity)) {
                $this->addCreated($entity);
            }
            // TraitOwnerInterface
            if ($entity instanceof TraitOwnerInterface && empty($entity->getOwner())) {
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
            // TraitDatetimedInterface
            if ($entity instanceof TraitDatetimedInterface && empty($entity->getLanguage())) {
                if($defaultLanguage = $this->appWire->getCurrentLanguage()) {
                    $entity->setLanguage($defaultLanguage);
                    // Default timezone setted automatically
                }
            }
            // TraitUnamedInterface
            if ($entity instanceof TraitUnamedInterface) {
                $this->postCreated($entity->getUname());
            }
            // if($service = $this->getEntityService($entity)) {
            //     $service->postCreated($entity);
            // }
        } else {
            // Model
        }
        $entity->getSelfState()->setPostCreated();
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
    ): ?BaseEntityInterface {
        $repo = $this->em->getRepository($classname);
        return $repo->find($id);
    }

    public function findEntityByEuid(
        string $euid
    ): ?BaseEntityInterface {
        $entity = $this->findCreated($euid);
        if (!$entity) {
            // Try in database...
            $class = Encoders::getClassOfEuid($euid);
            $repo = $this->em->getRepository($class);
            $entity = $repo->findOneBy(['euid' => $euid]);
        }
        return $entity instanceof BaseEntityInterface ? $entity : null;
    }

    public function findEntityByUname(
        string $uname
    ): ?BaseEntityInterface {
        $entity = $this->findCreated($uname);
        if (!$entity) {
            // Try in database...
            $unameOjb = $this->getRepository(Uname::class)->find($uname);
            $entity = $unameOjb instanceof UnameInterface
                ? $this->findEntityByEuid($unameOjb->getEntityEuid())
                : null;
        }
        return $entity instanceof BaseEntityInterface ? $entity : null;
    }

    public function getEuidOfUname(
        string $uname
    ): ?string
    {
        if(Encoders::isEuidFormatValid($uname)) {
            $unameOjb = $this->getRepository(Uname::class)->find($uname);
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
            : $this->findEntityByUname($value, false);
    }

    public function getClassnameByUname(
        string $uname
    ): ?string
    {
        if($entity = $this->findCreated($uname)) {
            return $entity->getClassname();
        }
        return $this->getRepository(Uname::class)->getClassnameByUname($uname);
    }

    public function getClassnameByEuidOrUname(
        string $euidOrUname
    ): ?string
    {
        if($entity = $this->findCreated($euidOrUname)) {
            return $entity->getClassname();
        }
        return Encoders::isEuidFormatValid($euidOrUname)
            ? Encoders::getClassOfEuid($euidOrUname)
            : $this->getRepository(Uname::class)->getClassnameByUname($euidOrUname);
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
     * @param string|BaseEntityInterface $objectOrClass
     * @return ClassMetadata|null
     */
    public function getClassMetadata(
        null|string|BaseEntityInterface $objectOrClass = null,
    ): ?ClassMetadata {
        $classname = $objectOrClass instanceof BaseEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        return $classname
            ? $this->em->getClassMetadata($classname)
            : null;
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
            case '||': $cache_name = 'entities_names'; break;
            case '1||': $cache_name = 'entities_shortnames'; break;
            case '||1': $cache_name = 'entities_names_instantiables'; break;
            case '1||1': $cache_name = 'entities_shortnames_instantiables'; break;
            case '|1|': $cache_name = 'entities_all_names'; break;
            case '1|1|': $cache_name = 'entities_all_shortnames'; break;
            case '|1|1': $cache_name = 'entities_all_names_instantiables'; break;
            case '1|1|1': $cache_name = 'entities_all_shortnames_instantiables'; break;
            default:
                if($this->appWire->isDev()) {
                    throw new Exception(vsprintf('Error %s line %d: invalid cache name "%s" for getAppEntityNames', [__METHOD__, __LINE__, $test]));
                }
                return $callback();
                break;
        }
        return $this->cacheService->get($cache_name, $callback);
    }

    /**
     * Get App entity names
     * 
     * @param bool $asShortnames
     * @param bool $onlyInstantiables
     * @return array
     */
    #[CacheManaged(name: 'app_entities_names', params: ['asShortnames' => false, 'onlyInstantiables' => false, 'commentaire' => 'Get AppWire entity names'])]
    #[CacheManaged(name: 'app_entities_shortnames', params: ['asShortnames' => true, 'onlyInstantiables' => false, 'commentaire' => 'Get AppWire entity shortnames'])]
    #[CacheManaged(name: 'app_entities_names_instantiables', params: ['asShortnames' => false, 'onlyInstantiables' => true, 'commentaire' => 'Get AppWire instantiable entity names'])]
    #[CacheManaged(name: 'app_entities_shortnames_instantiables', params: ['asShortnames' => true, 'onlyInstantiables' => true, 'commentaire' => 'Get AppWire instantiable entity shortnames'])]
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
            case '|': $cache_name = 'app_entities_names'; break;
            case '1|': $cache_name = 'app_entities_shortnames'; break;
            case '|1': $cache_name = 'app_entities_names_instantiables'; break;
            case '1|1': $cache_name = 'app_entities_shortnames_instantiables'; break;
            default:
                if($this->appWire->isDev()) {
                    throw new Exception(vsprintf('Error %s line %d: invalid cache name "%s" for getAppEntityNames', [__METHOD__, __LINE__, $test]));
                }
                return $callback();
                break;
        }
        return $this->cacheService->get($cache_name, $callback);
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
        $cache_name = $asShortnames ? 'between_entities_shortnames' : 'between_entities_names';
        return $this->cacheService->get($cache_name, $callback);
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
        $cache_name = $asShortnames ? 'translation_entities_shortnames' : 'translation_entities_names';
        return $this->cacheService->get($cache_name, $callback);
    }

    #[CacheManaged(name: 'final_entities_names', params: ['asShortnames' => false, 'allnamespaces' => false, 'commentaire' => 'Get AppWire entity names'])]
    #[CacheManaged(name: 'final_entities_shortnames', params: ['asShortnames' => true, 'allnamespaces' => false, 'commentaire' => 'Get AppWire entity shortnames'])]
    #[CacheManaged(name: 'final_all_entities_names', params: ['asShortnames' => false, 'allnamespaces' => true, 'commentaire' => 'Get AppWire instantiable entity names'])]
    #[CacheManaged(name: 'final_all_entities_shortnames', params: ['asShortnames' => true, 'allnamespaces' => true, 'commentaire' => 'Get AppWire instantiable entity shortnames'])]
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
            case '|': $cache_name = 'final_entities_names'; break;
            case '1|': $cache_name = 'final_entities_shortnames'; break;
            case '|1': $cache_name = 'final_all_entities_names'; break;
            case '1|1': $cache_name = 'final_all_entities_shortnames'; break;
            default:
                if($this->appWire->isDev()) {
                    throw new Exception(vsprintf('Error %s line %d: invalid cache name "%s" for getAppEntityNames', [__METHOD__, __LINE__, $test]));
                }
                return $callback();
                break;
        }
        return $this->cacheService->get($cache_name, $callback);

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

    /**
     * Get all related classnames of entity
     * 
     * @param string|BaseEntityInterface $objectOrClass
     * @param bool $recursive
     * @return array
     */
    public function getRelatedClassnames(
        string|BaseEntityInterface $objectOrClass,
        ?Closure $filter = null
    ): array
    {
        $classname = $objectOrClass instanceof BaseEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        $this->surveyRecursion(__METHOD__.'::'.$classname);
        $primarys = $this->getRelateds($objectOrClass, $filter, true);
        $filter_recursives = $keys = array_keys($primarys);
        $relateds = [];
        foreach ($keys as $class) {
            $relateds[$class] = Objects::getShortname($class);
            $filter_recursives = array_unique(array_merge($filter_recursives, array_keys($relateds), $keys));
            $fars = $this->getRelatedClassnames($class, fn(AssociationMapping $mapping, ClassMetadata $cmd) => !in_array($mapping->targetEntity, $filter_recursives), true);
            if(!empty($fars)) {
                $relateds = array_merge($relateds, $fars);
            }
        }
        return $relateds;
    }

    /**
     * Get all related properties of entity
     * - add properties of relations not defined in the class metadata
     * 
     * @param string|BaseEntityInterface $objectOrClass
     * @param array $filterFields
     * @param null|Closure $filter
     * @return array
     */
    public function getAllRelatedProperties(
        string|BaseEntityInterface $objectOrClass,
        array $filterFields = [],
        ?Closure $filter = null,
    ): array
    {
        $filterAsSource = is_callable($filter) && true;
        $classname = $objectOrClass instanceof BaseEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        // A - Regular associations
        $cmd = $this->getClassMetadata($classname);
        $associations = $cmd->getAssociationMappings();
        // B - Relative associations
        $mappings = $this->getRelativeRelationMappings($classname);
        /*****************************/
        /** compile all associations */
        /*****************************/
        $final_mappings = [];
        // 1 - added: from relative mappings
        foreach ($mappings as $field => $mapping) {
            if($filterAsSource && !$filter($associations[$mapping['field']], $field, $cmd)) {
                continue;
            }
            if(empty($filterFields) || in_array($field, $filterFields)) {
                $final_mappings[$field] = $mapping;
                // $mappings[$field]['field'] --> already set
                $mappings[$field]['require'] = (array)$mappings[$field]['require'];
                $final_mappings[$field]['property'] = $field;
                $final_mappings[$field]['mapping'] = $associations[$mapping['field']];
            }
        }
        // 2 - default: from class metadata
        foreach ($associations as $field => $mapping) {
            if($filterAsSource && !$filter($mapping, $field, $cmd)) {
                continue;
            }
            if(empty($filterFields) || in_array($field, $filterFields)) {
                $final_mappings[$field] ??= [
                    'property' => $mapping->fieldName,
                    'field' => $mapping->fieldName,
                    'mapping' => $mapping,
                    'require' => (array)$mapping->targetEntity,
                ];
            }
        }
        // Check classnames
        foreach ($final_mappings as $field => $mapping) {
            if($filterAsSource && !$filter($mapping['mapping'], $field, $cmd)) {
                continue;
            }
            $mapp_require = [];
            // Transorm abstract & instances in final entity classes
            foreach ($final_mappings[$field]['require'] as $require) {
                /** @var string $require */
                if(is_a($require, BetweenManyInterface::class, true)) {
                    // Is a between entity
                    $between = $this->getClassMetadata($final_mappings[$field]['mapping']->targetEntity);
                    foreach ($between->getAssociationMappings() as $mapp) {
                        if($mapp->targetEntity !== $classname) {
                            $mapp_require = array_merge($mapp_require, $this->resolveFinalEntitiesByNames($mapp->targetEntity, true));
                            // dump($mapp->targetEntity, $mapp_require);
                            break;
                        }
                    }
                }
                $mapp_require = array_merge($mapp_require, $this->resolveFinalEntitiesByNames($final_mappings[$field]['require'], true));
            }
            // if(count($mapp_require) === 1) {
            //     $mapp_require = reset($mapp_require);
            // }
            $final_mappings[$field]['require'] = array_unique($mapp_require);
            if(empty($final_mappings[$field]['require'])) {
                throw new Exception(vsprintf('Error %s line %d: could not find required target entity for "%s" relation!', [__METHOD__, __LINE__, $field]));
            }
            // Control
            if(!$this->appWire->isProd()) {
                // Check if requires are valid
                $errors = [];
                foreach ($final_mappings[$field]['require'] as $classname) {
                    if(!$this->entityExists($classname, true, true)) {
                        $errors[] = vsprintf('- %s relation property "%s" (relation: %s => %s) %s is not instantiable!', [$classname, $field, $final_mappings[$field]['field'], $final_mappings[$field]['mapping']->targetEntity, $classname]);
                    }
                    if(count($errors)) {
                        throw new Exception(vsprintf('Error %s line %d:%s%s%sRequired classnames are:%s', [__METHOD__, __LINE__, PHP_EOL.PHP_EOL, implode(PHP_EOL, $errors), PHP_EOL.PHP_EOL, PHP_EOL.'- '.implode(PHP_EOL.'- ', (array)$final_mappings[$field]['require'])]));
                    }
                }
            }
        }
        if(!$filterAsSource && is_callable($filter)) {
            // Filter final mappings
            // $filter = function(AssociationMapping $mapping, string $field, ClassMetadata $cmd): bool
            return array_filter(
                $final_mappings, 
                fn($mapping, $field) => $filter($mapping['mapping'], $field, $cmd),
                ARRAY_FILTER_USE_BOTH
            );
        }
        return $final_mappings;
    }

    /**
     * Get relative relation mappings
     * Returns properties of relations not defined in the class metadata
     * 
     * @param string|BaseEntityInterface $classname
     * @return array
     */
    protected function getRelativeRelationMappings(
        string|BaseEntityInterface $classname,
    ): array
    {
        if(static::SERIALIZATION_MAPPINGS_BY_ATTRIBUTE) {
            // Get serialization mappings by SerializationMapping attribute
            $mappings = Objects::getClassAttributes($classname, SerializationMapping::class);
            // Get first mapping
            /** @var SerializationMapping|false $mapping */
            $mapping = reset($mappings);
            return $mapping ? $mapping->getMapping() : [];
        }
        // Get serialization mappings by class constant ITEMS_ACCEPT
        $constant = $classname.'::ITEMS_ACCEPT';
        return defined($constant) ? constant($constant) : [];
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


    private function surveyRecursion(
        string $name,
        ?int $max = null
    ): void {
        if ($this->appWire->isDev()) {
            $max ??= self::MAX_SURVEY_RECURSION;
            $this->__src[$name] ??= 0;
            $this->__src[$name]++;
            if ($this->__src[$name] > $max) {
                throw new Exception(vsprintf('Error %s line %d: "%s" recursion limit %d reached!', [__METHOD__, __LINE__, $name, $max]));
            }
        }
    }

}
