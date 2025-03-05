<?php
namespace Aequation\WireBundle\Service;

// Aequation
use Aequation\WireBundle\Component\EntityEmbededStatus;
use Aequation\WireBundle\Entity\interface\TraitOwnerInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Serializer\EntityDenormalizer;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
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

    protected ArrayCollection $createds;
    protected readonly UnitOfWork $uow;

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
        public readonly NormalizerServiceInterface $normalizer,
        protected UploaderHelper $vichHelper,
        protected CacheManager $liipCache
    )
    {
        // $this->uow = $this->em->getUnitOfWork();
        $this->createds = new ArrayCollection();
    }


    /****************************************************************************************************/
    /** CREATED                                                                                         */
    /****************************************************************************************************/

    public function addCreated(WireEntityInterface $entity): void
    {
        if($this->appWire->isDev()) {
            // DEV controls
            $this->checkIntegrity($entity, 'addCreated');
        }
        $index = spl_object_hash($entity);
        if(!$this->createds->containsKey($index)) {
            $this->createds->set($index, $entity);
        } else if($this->appWire->isDev()) {
            $exists = $this->createds->get($index);
            throw new Exception(vsprintf('Error %s line %d: entity with %s already exists!%s- 1 - %s %s%s- 2 - %s %s', [__METHOD__, __LINE__, $index, PHP_EOL, $entity->getClassname(), $entity->__toString(), PHP_EOL, $exists->getClassname(), $exists->__toString()]));
        }
    }

    public function hasCreated(WireEntityInterface $entity): bool
    {
        $index = spl_object_hash($entity);
        return $this->createds->containsKey($index);
        // return $this->createds->contains($entity);
    }

    public function clearCreateds(): bool
    {
        $this->createds->clear();
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
    ): ?WireEntityInterface
    {
        foreach($this->createds as $entity) {
            /** @var WireEntityInterface $entity */
            if($entity->getEuid() === $euidOrUname) {
                return $entity;
            }
            if($entity instanceof TraitUnamedInterface && $entity->getUnameName() === $euidOrUname) {
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
    ): ?WireEntityServiceInterface
    {
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

    /**
     * After a new entity created, add it to createds list and more actions...
     * 
     * @param WireEntityInterface $entity
     * @return void
     */
    public function postCreatedRealEntity(
        WireEntityInterface $entity,
        bool $asModel = false
    ): void
    {
        if(!$entity->hasEmbededStatus()) {
            new EntityEmbededStatus($entity, $this->appWire);
        }
        if(!$asModel) {
            // Real entity
            if(!$this->hasCreated($entity)) {
                $this->addCreated($entity);
            }
            if($entity instanceof TraitOwnerInterface) {
                // Owner
                $user = $this->appWire->getUser();
                if($user) {
                    $entity->setOwner($user);
                } else if($entity->isOwnerRequired()) {
                    $userService = $this->appWire->get(WireUserServiceInterface::class);
                    $admin = $userService->getMainAdmin();
                    if($admin) {
                        $entity->setOwner($admin);
                    } else if($this->appWire->isDev()) {
                        throw new Exception(vsprintf('Error %s line %d: entity %s %s has no owner!', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
                    }
                }
            }
            if($entity instanceof TraitUnamedInterface) {
                $this->postCreatedRealEntity($entity->getUname(), $asModel);
            }
        } else {
            // Model
            $entity->__estatus->setModel(true);
        }
    }

    protected function createNewEntity(
        string $classname,
        ?array $data = [], // ---> do not forget uname if wanted!
        ?array $context = []
    ): WireEntityInterface
    {
        $service = $this->getEntityService($classname);
        if($service instanceof WireEntityServiceInterface) {
            return $service->createEntity($data, $context);
        }
        if(empty($context['groups'] ?? null)) {
            $norm_groups = EntityDenormalizer::getNormalizeGroups($classname, type: 'hydrate');
            $context['groups'] = $norm_groups['denormalize'];
        }
        $entity = $this->normalizer->denormalizeEntity($data, $classname, null, $context);
        return $entity;
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
        ?array $data = [], // ---> do not forget uname if wanted!
        ?array $context = []
    ): WireEntityInterface
    {
        if(empty($context['groups'] ?? null)) {
            $norm_groups = EntityDenormalizer::getNormalizeGroups($classname, type: 'hydrate');
            $context['groups'] = $norm_groups['denormalize'];
        }
        $entity = $this->createNewEntity($classname, $data, $context);
        $this->postCreatedRealEntity($entity, false);
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
        ?array $data = [], // ---> do not forget uname if wanted!
        ?array $context = []
    ): WireEntityInterface
    {
        if(empty($context['groups'] ?? null)) {
            $norm_groups = EntityDenormalizer::getNormalizeGroups($classname, type: 'model');
            $context['groups'] = $norm_groups['denormalize'];
        }
        $model = $this->createNewEntity($classname, $data, $context);
        $this->postCreatedRealEntity($model, true);
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
        ?array $context = []
    ): WireEntityInterface|false
    {
        $classname = $entity->getClassname();
        $norm_groups = EntityDenormalizer::getNormalizeGroups($entity, type: 'clone');
        if(empty($context['groups'] ?? null)) {
            $context['groups'] = $norm_groups['normalize'];
        }
        $data = $this->normalizer->normalizeEntity($entity, null, $context);
        $context['groups'] = $norm_groups['denormalize'];
        $clone = $this->createEntity($classname, array_merge($data, $changes));
        // Add some stuff here...
        return $clone;
    }


    /****************************************************************************************************/
    /** CHECK / INTEGRITY                                                                               */
    /****************************************************************************************************/

    /**
     * Check integrity of entity
     *
     * @param WireEntityInterface $entity
     * @return void
     */
    public function checkIntegrity(
        WireEntityInterface $entity,
        null|EventArgs|string $event = null
    ): void
    {
        if($this->appWire->isProd()) return;
        // Only for dev
        if($event instanceof EventArgs) {
            $eventname = $event::class;
        } else if(is_string($event)) {
            // if(!is_a($event, EventArgs::class, true)) {
            //     throw new Exception(vsprintf('Error %s line %d: %s is not an instance of %s!', [__METHOD__, __LINE__, $event, EventArgs::class]));
            // }
            $eventname = $event;
        } else {
            $eventname = null;
        }
        switch ($eventname) {
            case PostLoadEventArgs::class:
                # code...
                break;
            case PrePersistEventArgs::class:
                // Check if entity is EmbededStatus
                if(empty($entity->__estatus)) {
                    throw new Exception(vsprintf('Error %s line %d: entity %s %s EmbededStatus is missing', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));  
                }
            case PreUpdateEventArgs::class:
                // Check if entity has a uname
                if($entity instanceof TraitUnamedInterface && empty($entity->getUname())) {
                    throw new Exception(vsprintf('Error %s line %d: entity %s %s has no uname', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));  
                }
                // Check if entity is not a model
                if(isset($entity->__estatus) && $entity->__estatus->isModel()) {
                    throw new Exception(vsprintf('Error %s line %d: entity %s %s is a model', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));  
                }
                break;
            case 'addCreated':
                // Check if entity is EmbededStatus
                if(empty($entity->__estatus)) {
                    throw new Exception(vsprintf('Error %s line %d: entity %s %s EmbededStatus is missing', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));  
                }
                // Check if entity has a uname
                if($entity instanceof TraitUnamedInterface && empty($entity->getUname())) {
                    throw new Exception(vsprintf('Error %s line %d: entity %s %s has no uname', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));  
                }
                // Check if entity is not a model
                if($entity->__estatus->isModel()) {
                    throw new Exception(vsprintf('Error %s line %d: entity %s %s is a model', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));  
                }
                // Entity must not be managed
                if($entity->__estatus->isContained()) {
                    throw new Exception(vsprintf('Error %s line %d: %s entity should not be managed for now!', [__METHOD__, __LINE__, $entity->getClassname()]));
                }
                break;
            default:
                // Check if entity is EmbededStatus
                // if(empty($entity->__estatus)) {
                //     throw new Exception(vsprintf('Error %s line %d: entity %s %s EmbededStatus is missing', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));  
                // }
                // Check if entity has a uname
                if($entity instanceof TraitUnamedInterface && empty($entity->getUname())) {
                    throw new Exception(vsprintf('Error %s line %d: entity %s %s has no uname', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));  
                }
                // Check if entity is not a model
                if(isset($entity->__estatus) && $entity->__estatus->isModel()) {
                    throw new Exception(vsprintf('Error %s line %d: entity %s %s is a model', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));  
                }
                break;
        }
    }


    /****************************************************************************************************/
    /** REPOSITORY / FIND                                                                               */
    /****************************************************************************************************/

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
    ): ?WireEntityInterface
    {
        if(false === ($entity = $this->createds->containsKey($euid) ? $this->createds->get($euid) : false)) {
            // Try in database...
            $class = Encoders::getClassOfEuid($euid);
            /** @var BaseWireRepositoryInterface */
            $repo = $this->em->getRepository($class);
            $entity = $repo->findOneByEuid($euid);
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
    ): ?WireEntityInterface
    {
        if(false === ($entity = $this->createds->containsKey($uname) ? $this->createds->get($uname) : false)) {
            // Try in database...
            $classes = $this->getEntityNames(false, false, true);
            foreach ($classes as $class) {
                if(is_a($class, TraitUnamedInterface::class, true)) {
                    /** @var string $class */
                    /** @var BaseWireRepositoryInterface */
                    $repo = $this->em->getRepository($class);
                    $entity = $repo->findEntityByEuidOrUname($uname);
                }
            }
        }
        return $entity instanceof WireEntityInterface ? $entity : null;
    }

    public function findEntityByUniqueValue(
        string $value
    ): ?WireEntityInterface
    {
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
    ): int
    {
        /** @var BaseWireRepositoryInterface */
        $repository = $this->em->getRepository($classname);
        return $repository->count($criteria);
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
    ): ?ClassMetadata
    {
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
    ): bool
    {
        return is_a($objectOrClass, WireEntityInterface::class, true);
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
            if(!$onlyInstantiables || $cmd->reflClass->isInstantiable()) {
                if($allnamespaces || static::isAppWireEntity($cmd->name)) {
                    $names[$cmd->name] = $asShortnames
                        ? $cmd->reflClass->getShortname()
                        : $cmd->name;
                }
            }
        }
        return $names;
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
    ): bool
    {
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
    ): array
    {
        $uniqueFields = [
            'hierar' => [],
            'flatlist' => [],
        ];
        foreach (Objects::getClassAttributes($classname, UniqueEntity::class, true) as $attr) {
            /** @var UniqueEntity $attr */
            $ufields = (array)$attr->fields;
            if(isset($ufields)) {
                $uniqueFields['hierar'][] = $ufields;
                $uniqueFields['flatlist'] = array_unique(array_merge($uniqueFields['flatlist'], $ufields));
            }
        }
        if(is_null($flatlisted)) return $uniqueFields;
        return $flatlisted
            ? $uniqueFields['flatlist']
            : $uniqueFields['hierar'];
    }

    /**
     * Get Doctrine relations of entity
     * 
     * @param string|WireEntityInterface $objectOrClass
     * @param string|array|null|null $relationTypes
     * @param boolean $excludeSelf
     * @return array
     */
    public function getRelateds(
        string|WireEntityInterface $objectOrClass,
        null|string|array $relationTypes = null, // -> ['ManyToMany','ManyToOne','OneToMany','OneToOne','One','OneTo','Many','ManyTo','ToOne','ToMany','',null]
        ?bool $excludeSelf = false
    ): array
    {
        $rt_test = $this->getRelationTypeRegex($relationTypes);
        $classname = $objectOrClass instanceof WireEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        $classnames = [];
        foreach ($this->getEntityNames(false, false, true) as $class) {
            if(!($excludeSelf && is_a($class, $classname, true))) {
                foreach ($this->getClassMetadata($class)->associationMappings as $associationMapping) {
                    $shortname = Objects::getShortname($associationMapping);
                    if(preg_match($rt_test, $shortname, $types)) {
                        $classnames[$class] = [
                            'mapping_object' => $associationMapping,
                            'mapping_type' => $shortname,
                        ];                        
                    }
                }
            }
        }
        return $classnames;
    }

    protected function getRelationTypeRegex(
        null|string|array $relationTypes
    ): string
    {
        $available_types = ['ManyToMany','ManyToOne','OneToMany','OneToOne','One','OneTo','Many','ManyTo','ToOne','ToMany','',null];
        if(!is_array($relationTypes) || empty($relationTypes)) $relationTypes = [$relationTypes];
        $relationTypes = array_filter($relationTypes, fn($type) => (is_scalar($type) || is_null($type)) && in_array($type, $available_types));
        if(empty($relationTypes)) $relationTypes = $available_types;
        $from = $to = [];
        foreach($relationTypes as $value) {
            $$value = explode('To', (string)$value);
            if(count($value) < 2) $value[] = '';
            // From
            if(in_array($value[0], ['One','Many'])) {
                $from[$value[0]] = $value[0];
            } else if(empty($value[0])) {
                $from = ['One' => 'One', 'Many' => 'Many'];
            }
            // To
            if(in_array($value[1], ['One','Many'])) {
                $to[$value[1]] = $value[1];
            } else if(empty($value[1])) {
                $to = ['One' => 'One', 'Many' => 'Many'];
            }
        }
        return '/^(('.implode('|', empty($from) ? ['One','Many'] : $from).')To('.implode('|', empty($to) ? ['One','Many'] : $to).'))/';
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
    ): ?string
    {
        $browserPath = $this->vichHelper->asset($media);
        if($filter && !($media instanceof WirePdfInterface)) {
            $browserPath = $this->liipCache->getBrowserPath($browserPath, $filter, $runtimeConfig, $resolver, $referenceType);
        }
        return $browserPath;
    }

}