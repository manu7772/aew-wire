<?php

namespace Aequation\WireBundle\Service;

// Aequation
use Aequation\WireBundle\Component\EntityEmbededStatus;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\NormalizeDataContainer;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Entity\interface\TraitOwnerInterface;
use Aequation\WireBundle\Entity\interface\TraitPreferedInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\HttpRequest;
use Aequation\WireBundle\Tools\Objects;
use Aequation\WireBundle\Tools\Strings;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Twig\Markup;
// PHP
use Exception;
use Closure;

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
    private array $__src = [];

    protected ArrayCollection $createds;
    protected NormalizerServiceInterface $normalizer;
    protected readonly UnitOfWork $uow;
    protected int $debug_mode = 0;

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

    public function addCreated(WireEntityInterface $entity): void
    {
        $index = spl_object_hash($entity);
        if (!$this->createds->containsKey($index)) {
            $this->createds->set($index, $entity);
        } else if ($this->appWire->isDev()) {
            $exists = $this->createds->get($index);
            throw new Exception(vsprintf('Error %s line %d: entity with %s already exists!%s- 1 - %s %s%s- 2 - %s %s', [__METHOD__, __LINE__, $index, PHP_EOL, $entity->getClassname(), $entity->__toString(), PHP_EOL, $exists->getClassname(), $exists->__toString()]));
        }
    }

    public function hasCreated(WireEntityInterface $entity): bool
    {
        return $this->createds->containsKey(spl_object_hash($entity));
    }

    public function clearCreateds(): bool
    {
        foreach ($this->createds as $key => $entity) {
            /** @var WireEntityInterface $entity */
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
     * @param WireEntityInterface $entity
     * @return bool
     */
    public function clearPersisteds(): bool
    {
        $this->createds = $this->createds->filter(fn($entity) => !$entity->__estatus->isContained());
        return $this->createds->isEmpty();
    }

    public function findCreated(
        string $euidOrUname
    ): ?WireEntityInterface {
        foreach ($this->createds as $entity) {
            /** @var WireEntityInterface $entity */
            if ($entity->getEuid() === $euidOrUname) {
                return $entity;
            }
            if ($entity instanceof TraitUnamedInterface && $entity->getUname()->getId() === $euidOrUname) {
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
     * @param string|WireEntityInterface $entity
     * @return ?WireEntityServiceInterface
     */
    public function getEntityService(
        string|WireEntityInterface $entity
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
        WireEntityInterface $entity
    ): void {
        if (!$entity->hasEmbededStatus()) {
            new EntityEmbededStatus($entity, $this->appWire);
        }
    }

    /**
     * create entity
     * 
     * @param string $classname
     * @param string|null $uname
     * @return WireEntityInterface
     */
    public function createEntity(
        string $classname,
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = [],
        bool $tryService = true
    ): WireEntityInterface {
        $this->surveyRecursion(__METHOD__.'::'.$classname);
        if($tryService && $service = $this->getEntityService($classname)) {
            return $service->createEntity($data, $context);
        }
        if(!$data || empty($data)) {
            $entity = new $classname();
            $this->postCreated($entity);
        } else {
            // Denormalize
            $normalizeContainer = new NormalizeDataContainer($this, $classname, $data, $context, null, true, false);
            $entity = $this->getNormaliserService()->denormalizeEntity($normalizeContainer, $classname);
        }
        // Add some stuff here...
        return $entity;
    }


    /**
     * create model
     * 
     * @return WireEntityInterface
     */
    public function createModel(
        string $classname,
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = [],
        bool $tryService = true
    ): WireEntityInterface {
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
            $normalizeContainer = new NormalizeDataContainer($this, $classname, $data, $context, null, true, true);
            $model = $this->getNormaliserService()->denormalizeEntity($normalizeContainer, $classname);
        }
        // Add some stuff here...
        return $model;
    }

    /**
     * create clone
     * 
     * @return WireEntityInterface|null
     */
    public function createClone(
        WireEntityInterface $entity,
        ?array $changes = [], // ---> do not forget uname if wanted!
        ?array $context = [],
        bool $tryService = true
    ): WireEntityInterface|false {
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
    /** MAINTAIN DATABASE                                                                               */
    /****************************************************************************************************/

    public function checkAllDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $opresult ??= new Opresult();
        foreach ($this->getEntityNames(false, false, true) as $classname) {
            $this->checkDatabase($classname, $opresult, $repair);
        }
        return $opresult;
    }

    public function checkDatabase(
        string $classname,
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->incDebugMode();
        $opresult ??= new Opresult();
        // Check prefered
        if(is_a($classname, TraitPreferedInterface::class, true)) {
            $this->database_check_prefered($classname, $opresult, $repair);
        }
        // Check others...
        // ...
        // Check specific functionalities for entity
        if($service = $this->getEntityService($classname)) {
            /** @var WireEntityServiceInterface $service */
            $service->checkDatabase($opresult, $repair);
        }
        $this->decDebugMode();
        return $opresult;
    }

    /**
     * Check TraitPreferedInterface
     */
    public function database_check_prefered(
        string $classname,
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->incDebugMode();
        $opresult ??= new Opresult();
        if(is_a($classname, TraitPreferedInterface::class, true)) {
            $repo = $this->getRepository($classname);
            $prefered = $repo->findBy(['prefered' => true]);
            if(count($prefered) > 1) {
                $opresult->addDanger(vsprintf('Error %s line %d: %s has more than one prefered (found %d)!', [__METHOD__, __LINE__, $classname, count($prefered)]));
                if($repair) {
                    $prefered = array_slice($prefered, 1);
                    foreach ($prefered as $entity) {
                        $entity->setPrefered(false);
                    }
                    $this->em->flush();
                    $this->em->clear();
                    $prefered = $repo->findBy(['prefered' => true]);
                    if(count($prefered) > 1) {
                        $opresult->addDanger(vsprintf('Error %s line %d: %s has more than one prefered after repair (still found %d)!', [__METHOD__, __LINE__, $classname, count($prefered)]));
                    } else {
                        $opresult->addSuccess(vsprintf('Success %s line %d: %s has been repaired!', [__METHOD__, __LINE__, $classname]));
                    }
                }
            }
        }
        $this->decDebugMode();
        return $opresult;
    }


    /****************************************************************************************************/
    /** ENTITY EVENTS                                                                                   */
    /****************************************************************************************************/

    /**
     * After a entity is loaded from database, add EntityEmbededStatus and more actions...
     * 
     * @param WireEntityInterface $entity
     * @return void
     */
    public function postLoaded(
        WireEntityInterface $entity
    ): void {
        if($entity->getSelfState() && $entity->getSelfState()?->isPostLoaded() ?? false) return;
        $entity->doInitializeSelfState('loaded', 'auto');
        $entity->getSelfState()->setPostLoaded();
        if($this->isDebugMode()) {
            $this->insertEmbededStatus($entity);
        }
    }

    /**
     * After a new entity created, add it to createds list and more actions...
     * 
     * @param WireEntityInterface $entity
     * @return void
     */
    public function postCreated(
        WireEntityInterface $entity
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
    /** CHECK / INTEGRITY                                                                               */
    /****************************************************************************************************/

    // /**
    //  * Check integrity of entity
    //  *
    //  * @param WireEntityInterface $entity
    //  * @return void
    //  */
    // public function checkIntegrity(
    //     WireEntityInterface $entity,
    //     null|EventArgs|string $event = null
    // ): void {
    //     if ($this->appWire->isProd()) return;
    //     // Only for dev
    //     if ($event instanceof EventArgs) {
    //         $eventname = $event::class;
    //     } else if (is_string($event)) {
    //         // if(!is_a($event, EventArgs::class, true)) {
    //         //     throw new Exception(vsprintf('Error %s line %d: %s is not an instance of %s!', [__METHOD__, __LINE__, $event, EventArgs::class]));
    //         // }
    //         $eventname = $event;
    //     } else {
    //         $eventname = null;
    //     }
    //     switch ($eventname) {
    //         case PostLoadEventArgs::class:
    //             # code...
    //             break;
    //         case PrePersistEventArgs::class:
    //             // Check if entity is EmbededStatus
    //             if (empty($entity->__estatus)) {
    //                 throw new Exception(vsprintf('Error %s line %d: entity %s %s EmbededStatus is missing', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
    //             }
    //         case PreUpdateEventArgs::class:
    //             // Check if entity has a uname
    //             if ($entity instanceof TraitUnamedInterface && empty($entity->getUname())) {
    //                 throw new Exception(vsprintf('Error %s line %d: entity %s %s has no uname', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
    //             }
    //             // Check if entity is not a model
    //             if (isset($entity->__estatus) && $entity->__estatus->isModel()) {
    //                 throw new Exception(vsprintf('Error %s line %d: entity %s %s is a model', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
    //             }
    //             break;
    //         case 'addCreated':
    //             // Check if entity is EmbededStatus
    //             if (empty($entity->__estatus)) {
    //                 throw new Exception(vsprintf('Error %s line %d: entity %s %s EmbededStatus is missing', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
    //             }
    //             // Check if entity has a uname
    //             if ($entity instanceof TraitUnamedInterface && empty($entity->getUname())) {
    //                 throw new Exception(vsprintf('Error %s line %d: entity %s %s has no uname', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
    //             }
    //             // Check if entity is not a model
    //             if ($entity->__estatus->isModel()) {
    //                 throw new Exception(vsprintf('Error %s line %d: entity %s %s is a model', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
    //             }
    //             // Entity must not be managed
    //             // if ($entity->__estatus->isContained()) {
    //             //     throw new Exception(vsprintf('Error %s line %d: %s %s entity should not be managed for now!', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
    //             // }
    //             // Entity must not come from database
    //             if (!empty($entity->getId())) {
    //                 throw new Exception(vsprintf('Error %s line %d: %s %s entity should not come from database!', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
    //             }
    //             break;
    //         default:
    //             // Check if entity is EmbededStatus
    //             // if(empty($entity->__estatus)) {
    //             //     throw new Exception(vsprintf('Error %s line %d: entity %s %s EmbededStatus is missing', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));  
    //             // }
    //             // Check if entity has a uname
    //             if ($entity instanceof TraitUnamedInterface && empty($entity->getUname())) {
    //                 throw new Exception(vsprintf('Error %s line %d: entity %s %s has no uname', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
    //             }
    //             // Check if entity is not a model
    //             if (isset($entity->__estatus) && $entity->__estatus->isModel()) {
    //                 throw new Exception(vsprintf('Error %s line %d: entity %s %s is a model', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
    //             }
    //             break;
    //     }
    // }


    /****************************************************************************************************/
    /** REPOSITORY / QUERYS                                                                             */
    /****************************************************************************************************/

    public function getRepository(string|WireEntityInterface $objectOrClass): ?EntityRepository
    {
        $classname = $objectOrClass instanceof WireEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
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
     * @param string $euid
     * @return WireEntityInterface|null
     */
    public function findEntityByEuid(
        string $euid
    ): ?WireEntityInterface {
        $entity = $this->findCreated($euid);
        if (!$entity) {
            // Try in database...
            $class = Encoders::getClassOfEuid($euid);
            $repo = $this->em->getRepository($class);
            $entity = $repo->findOneBy(['euid' => $euid]);
        }
        return $entity instanceof WireEntityInterface ? $entity : null;
    }

    /**
     * find entity by uname
     * 
     * @param string $uname
     * @return WireEntityInterface|null
     */
    public function findEntityByUname(
        string $uname
    ): ?WireEntityInterface {
        $entity = $this->findCreated($uname);
        if (!$entity) {
            // Try in database...
            $unameOjb = $this->getRepository(Uname::class)->find($uname);
            $entity = $unameOjb instanceof UnameInterface
                ? $this->findEntityByEuid($unameOjb->getEntityEuid())
                : null;
        }
        return $entity instanceof WireEntityInterface ? $entity : null;
    }

    public function findEntityByUniqueValue(
        string $value
    ): ?WireEntityInterface {
        return Encoders::isEuidFormatValid($value)
            ? $this->findEntityByEuid($value)
            : $this->findEntityByUname($value, false);
    }

    /**
     * get count of entities
     * can use criteria
     * 
     * @param string $classname
     * @param array $criteria
     * @return WireEntityInterface|null
     */
    public function getEntitiesCount(
        string $classname,
        array $criteria = []
    ): int {
        /** @var BaseWireRepositoryInterface */
        $repo = $this->em->getRepository($classname);
        return $repo->count($criteria);
    }


    /************************************************************************************************************/
    /** ENTITY INFO                                                                                             */
    /************************************************************************************************************/

    /**
     * get class metadata
     * 
     * @see https://phpdox.net/demo/Symfony2/classes/Doctrine_ORM_Mapping_ClassMetadata.xhtml
     * @param string|WireEntityInterface $objectOrClass
     * @return ClassMetadata|null
     */
    public function getClassMetadata(
        null|string|WireEntityInterface $objectOrClass = null,
    ): ?ClassMetadata {
        $classname = $objectOrClass instanceof WireEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        return $classname
            ? $this->em->getClassMetadata($classname)
            : null;
    }

    /**
     * is AppWire entity
     * 
     * @param string|object $objectOrClass
     * @return bool
     */
    public static function isAppWireEntity(
        string|object $objectOrClass
    ): bool {
        return is_string($objectOrClass)
            ? is_a($objectOrClass, WireEntityInterface::class, true)
            : $objectOrClass instanceof WireEntityInterface;
    }

    /**
     * get entity names
     * 
     * @param bool $asShortnames
     * @param bool $allnamespaces
     * @param bool $onlyInstantiables
     * @return array
     */
    public function getEntityNames(
        bool $asShortnames = false,
        bool $allnamespaces = false,
        bool $onlyInstantiables = false,
    ): array
    {
        $names = [];
        // or $this->em->getConfiguration()->getEntityNamespaces() as $classname
        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $cmd) {
            /** @var ClassMetadata $cmd */
            if (!$onlyInstantiables || $cmd->reflClass->isInstantiable()) {
                if ($allnamespaces || static::isAppWireEntity($cmd->name)) {
                    $names[$cmd->name] = $asShortnames
                        ? $cmd->reflClass->getShortname()
                        : $cmd->name;
                }
            }
        }
        return $names;
    }

    public function getFinalEntities(
        bool $asShortnames = false,
        bool $allnamespaces = false,
    ): array
    {
        $finals = [];
        foreach ($this->getEntityNames($asShortnames, $allnamespaces, false) as $name => $shortname) {
            $cmd = $this->getClassMetadata($name);
            if (count($cmd->subClasses) === 0 && !$cmd->isMappedSuperclass) {
                $finals[$name] = $shortname;
            }
        }
        return $finals;
    }

    // public function getEntityNamesChoices(
    //     bool $asHtml = false,
    //     string|false $icon_type = 'fa', // only if $asHtml is true
    //     bool $allnamespaces = false,
    //     bool $onlyInstantiables = false
    // ): array
    // {
    //     return array_flip(
    //         array_map(
    //             fn($name) => $asHtml ? $this->getEntityNameAsHtml($name, $icon_type) : $name,
    //             $this->getEntityNames(false, $allnamespaces, $onlyInstantiables)
    //         )
    //     );
    // }

    // public function getEntityNameAsHtml(
    //     string|WireEntityInterface $classOrEntity,
    //     string|false $icon_type = false,
    //     bool $addClassname = true
    // ): Markup
    // {
    //     $classOrEntity = $classOrEntity instanceof WireEntityInterface
    //         ? $classOrEntity->getClassname()
    //         : $classOrEntity;
    //     switch ($icon_type) {
    //         case 'ux':
    //             $icon_type = '<twig:ux:icon name="'.$classOrEntity::getIcon($icon_type).'" />&nbsp;&nbsp;';
    //             break;
    //         case 'fa':
    //             $icon_type = '<i class="fa '.$classOrEntity::getIcon($icon_type).' fa-fw text-info"></i>&nbsp;&nbsp;';
    //             break;
    //         default:
    //             $icon_type = '';
    //             break;
    //     }
    //     $html = $icon_type.'<strong>'.Objects::getShortname($classOrEntity).'</strong>'.($addClassname ? '&nbsp;<small><i class="text-muted">'.$classOrEntity.'</i></small>' : '');
    //     return Strings::markup($html);
    // }

    /**
     * Get all entity classnames of interfaces
     * @param string|array $interfaces
     * @param boolean $allnamespaces = false
     * @return array
     */
    public function getEntityClassesOfInterface(
        string|array $interfaces,
        bool $allnamespaces = false,
        bool $onlyInstantiables = false
    ): array
    {
        return array_filter(
            Objects::filterByInterface($interfaces, $this->getEntityNames(false, $allnamespaces)),
            fn ($class) => $this->entityExists($class, $allnamespaces, $onlyInstantiables)
        );
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
        bool $allnamespaces = false,
        bool $onlyInstantiables = false,
    ): bool {
        $list = $this->getEntityNames(true, $allnamespaces, $onlyInstantiables);
        return in_array($classname, $list) || array_key_exists($classname, $list);
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
        foreach (Objects::getClassAttributes($classname, UniqueEntity::class, true) as $attr) {
            /** @var UniqueEntity $attr */
            $ufields = (array)$attr->fields;
            if (isset($ufields)) {
                $uniqueFields['hierar'][] = $ufields;
                $uniqueFields['flatlist'] = array_unique(array_merge($uniqueFields['flatlist'], $ufields));
            }
        }
        if (is_null($flatlisted)) return $uniqueFields;
        return $flatlisted
            ? $uniqueFields['flatlist']
            : $uniqueFields['hierar'];
    }

    /**
     * Get Doctrine relations of entity
     * 
     * @param string|WireEntityInterface $objectOrClass
     * @param null|Closure $filter
     * @param boolean $excludeSelf
     * @return array
     */
    public function getRelateds(
        string|WireEntityInterface $objectOrClass,
        ?Closure $filter = null,
        bool $excludeSelf = false
    ): array
    {
        $classname = $objectOrClass instanceof WireEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
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


    private function surveyRecursion(
        string $name
    ): void {
        if ($this->appWire->isDev()) {
            $this->__src[$name] ??= 0;
            $this->__src[$name]++;
            if ($this->__src[$name] > self::MAX_SURVEY_RECURSION) {
                throw new Exception(vsprintf('Error %s line %d: "%s" recursion limit (%d) reached!', [__METHOD__, __LINE__, $name, self::MAX_SURVEY_RECURSION]));
            }
        }
    }

}
