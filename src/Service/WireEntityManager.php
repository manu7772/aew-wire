<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Event\WireEntityEvent;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
// PHP
use Exception;

#[AsAlias(WireEntityManagerInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class WireEntityManager extends BaseService implements WireEntityManagerInterface
{

    public readonly ArrayCollection $createds;
    public readonly UnitOfWork $uow;

    public function __construct(
        protected EntityManagerInterface $em,
        protected AppWireServiceInterface $appWire,
        protected EventDispatcherInterface $eventDispatcher,
        protected UploaderHelper $vichHelper,
        protected CacheManager $liipCache
    )
    {
        $this->uow = $this->em->getUnitOfWork();
        $this->createds = new ArrayCollection();
    }


    public function getAppWireService(): AppWireServiceInterface
    {
        return $this->appWire;
    }

    public function getEntityService(
        string|WireEntityInterface $entity
    ): WireEntityManagerInterface|WireEntityServiceInterface
    {
        $service = $this->appWire->getClassService($entity);
        return $service ? $service : $this;
    }


    /****************************************************************************************************/
    /** PERSISTANCE                                                                                     */
    /****************************************************************************************************/

    public function persist(
        WireEntityInterface $entity,
        bool $flush = false
    ): static
    {
        $service = $this->getEntityService($entity);
        return $service instanceof WireEntityServiceInterface
            ? $service->persist($entity, $flush)
            : $this->__innerPersist($entity, $flush);
    }

    public function __innerPersist(
        WireEntityInterface $entity,
        bool $flush = false
    ): static
    {
        $entity->_estatus->failIfNotManageable(__METHOD__, __LINE__);
        if($entity->_estatus->requireDispatchEvent(WireEntityEvent::BEFORE_PERSIST)) {
            $this->eventDispatcher->dispatch(new WireEntityEvent($entity, $this), WireEntityEvent::BEFORE_PERSIST);
        }
        $this->em->persist($entity);
        if($flush) $this->flush();
        return $this;
    }

    public function update(
        WireEntityInterface $entity,
        bool $flush = false
    ): static
    {
        $service = $this->getEntityService($entity);
        return $service instanceof WireEntityServiceInterface
            ? $service->update($entity, $flush)
            : $this->__innerUpdate($entity, $flush);
    }

    public function __innerUpdate(
        WireEntityInterface $entity,
        bool $flush = false
    ): static
    {
        $entity->_estatus->failIfNotManageable(__METHOD__, __LINE__);
        if($entity->_estatus->requireDispatchEvent(WireEntityEvent::BEFORE_UPDATE)) {
            $this->eventDispatcher->dispatch(new WireEntityEvent($entity, $this), WireEntityEvent::BEFORE_UPDATE);
        }
        if($flush) $this->flush();
        return $this;
    }

    public function remove(
        WireEntityInterface $entity,
        bool $flush = false
    ): static
    {
        $service = $this->getEntityService($entity);
        return $service instanceof WireEntityServiceInterface
            ? $service->remove($entity, $flush)
            : $this->__innerRemove($entity, $flush);
    }

    public function __innerRemove(
        WireEntityInterface $entity,
        bool $flush = false
    ): static
    {
        $entity->_estatus->failIfNotManageable(__METHOD__, __LINE__);
        if($entity->_estatus->requireDispatchEvent(WireEntityEvent::BEFORE_REMOVE)) {
            $this->eventDispatcher->dispatch(new WireEntityEvent($entity, $this), WireEntityEvent::BEFORE_REMOVE);
        }
        $this->em->remove($entity);
        if($flush) $this->flush();
        return $this;
    }

    public function flush(): static
    {
        if($this->appWire->isDev()) {
            foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
                if($entity instanceof WireEntityInterface) {
                    $entity->_estatus->failIfNotManageable(__METHOD__, __LINE__);
                }
            }
        }
        foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
            if($entity instanceof WireEntityInterface) {
                if($this->appWire->isDev()) $entity->_estatus->failIfNotManageable(__METHOD__, __LINE__);
                if($entity->_estatus->requireDispatchEvent(WireEntityEvent::BEFORE_UPDATE)) {
                //     $this->eventDispatcher->dispatch(new WireEntityEvent($entity, $this), WireEntityEvent::BEFORE_UPDATE);
                // } else if($this->appWire->isDev()) {
                    if($this->appWire->isDev()) throw new Exception(vsprintf('Error %s line %d: Entity %s is scheduled for update, but Event %s was not triggered)!', [__METHOD__, __LINE__, $entity->getClassname(), WireEntityEvent::BEFORE_UPDATE]));
                }
            }
        }
        foreach ($this->uow->getScheduledEntityDeletions() as $entity) {
            if($entity instanceof WireEntityInterface) {
                if($this->appWire->isDev()) $entity->_estatus->failIfNotManageable(__METHOD__, __LINE__);
                if($entity->_estatus->requireDispatchEvent(WireEntityEvent::BEFORE_REMOVE)) {
                //     $this->eventDispatcher->dispatch(new WireEntityEvent($entity, $this), WireEntityEvent::BEFORE_REMOVE);
                // } else if($this->appWire->isDev()) {
                    if($this->appWire->isDev()) throw new Exception(vsprintf('Error %s line %d: Entity %s is scheduled for deletion, but Event %s was not triggered)!', [__METHOD__, __LINE__, $entity->getClassname(), WireEntityEvent::BEFORE_UPDATE]));
                }
            }
        }
        $this->em->flush();
        return $this;
    }


    /****************************************************************************************************/
    /** GENERATION                                                                                      */
    /****************************************************************************************************/

    /**
     * Get new entity instance of WireEntityInterface
     * @param string $classname
     * @return WireEntityInterface
     */
    public function createEntity(
        string $classname,
        string $uname = null
    ): WireEntityInterface
    {
        $service = $this->getEntityService($classname);
        return $service instanceof WireEntityServiceInterface
            ? $service->createEntity($uname)
            : $this->__innerCreateEntity($classname, $uname);
    }

    public function __innerCreateEntity(
        string $classname,
        string $uname = null
    ): WireEntityInterface
    {
        /** @var WireEntityInterface */
        $new = new $classname();
        if($new instanceof TraitUnamedInterface && !empty($uname)) {
            if(Uname::isValidUname($uname)) {
                $new->updateUname($uname);
            } else if($this->appWire->isDev()) {
                throw new Exception(vsprintf('Error %s line %d: this uname %s is invalid for new entity %s!', [__METHOD__, __LINE__, json_encode($uname)]), $new->getClassname());
            }
        }
        $this->createds->set($new->getUnameThenEuid(), $new);
        $this->eventDispatcher->dispatch(new WireEntityEvent($new, $this), WireEntityEvent::POST_CREATE);
        return $new;
    }

    public function createModel(
        string $classname
    ): WireEntityInterface
    {
        $service = $this->getEntityService($classname);
        return $service instanceof WireEntityServiceInterface
            ? $service->createModel()
            : $this->__innerCreateModel($classname);
    }

    public function __innerCreateModel(
        string $classname
    ): WireEntityInterface
    {
        $model = new $classname();
        $this->eventDispatcher->dispatch(new WireEntityEvent($model, $this), WireEntityEvent::POST_MODEL);
        return $model;
    }

    public function createClone(
        WireEntityInterface $entity,
        string $uname = null,
        int $clone_method = 1
    ): ?WireEntityInterface
    {
        $service = $this->getEntityService($entity);
        return $service instanceof WireEntityServiceInterface
            ? $service->createClone($entity, $uname, $clone_method)
            : $this->__innerCreateClone($entity, $uname, $clone_method);
    }

    public function __innerCreateClone(
        WireEntityInterface $entity,
        string $uname = null,
        int $clone_method = 1
    ): ?WireEntityInterface
    {
        $clone = null;
        if($entity->_estatus->isClonable()) {
            /** @var WireEntityInterface|TraitClonableInteface $entity */
            switch ($clone_method) {
                case static::CLONE_METHOD_WIRE:
                    $clone = clone $entity;
                    if($clone instanceof TraitUnamedInterface && !empty($uname)) {
                        if(Uname::isValidUname($uname)) {
                            $clone->updateUname($uname);
                        } else if($this->appWire->isDev()) {
                            throw new Exception(vsprintf('Error %s line %d: this uname %s is invalid for (cloned) entity %s!', [__METHOD__, __LINE__, json_encode($uname), $clone->getClassname()]));
                        }
                    }
                    break;
                case static::CLONE_METHOD_WITH:
                    /** @var TraitClonableInteface $entity */
                    $data = [
                        'id' => null,
                        '_estatus' => null,
                    ];
                    $clone = $entity->with($data);
                    break;
                case static::CLONE_METHOD_WILD:
                    $clone = clone $entity;
                    break;
            }
            if($clone instanceof WireEntityInterface) {
                $this->eventDispatcher->dispatch(new WireEntityEvent($clone, $this), WireEntityEvent::POST_CLONE);
            }
        }
        return $clone;
    }

    /****************************************************************************************************/
    /** REPOSITORY / FIND                                                                               */
    /****************************************************************************************************/

    public function getRepository(
        string $classname,
        string $field = null // if field, find repository where is declared this $field
    ): BaseWireRepositoryInterface
    {
        $cmd = $this->getClassMetadata($classname);
        $classname = $cmd->name;
        if($field) {
            // Find classname where field is declared
            if(array_key_exists($field, $cmd->fieldMappings)) {
                $test_classname = $cmd->fieldMappings[$field]->declared ?? $classname;
            } else if(array_key_exists($field, $cmd->associationMappings)) {
                $test_classname = $cmd->associationMappings[$field]->declared ?? $classname;
            } else {
                // Not found, tant pis...
            }
            if(isset($test_classname)) {
                $test_cmd = $this->getClassMetadata($test_classname);
                if(!$test_cmd->isMappedSuperclass) $classname = $test_classname;
            }
        }
        /** @var BaseWireRepositoryInterface */
        $repo = $this->em->getRepository($classname);
        // if(!empty($field)) dump($classname, $field, get_class($repo));
        if($this->appWire->isDev() && !($repo instanceof BaseWireRepositoryInterface)) {
            dd($this->__toString(), $classname, $cmd, $cmd->name, $repo);
        }
        return $repo;
    }

    /**
     * Get entity by EUID
     * @param string $euid
     * @return WireEntityInterface|null
     */
    public function findEntityByEuid(
        string $euid
    ): ?WireEntityInterface
    {
        
        if(false === ($entity = $this->createds->containsKey($euid) ? $this->createds->get($euid) : false)) {
            $class = Encoders::getClassOfEuid($euid);
            /** @var BaseWireRepositoryInterface */
            $repo = $this->getRepository($class);
            $entity = $repo->findOneByEuid($euid);
        }
        return $entity instanceof WireEntityInterface ? $entity : null;
    }

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
                    $repo = $this->getRepository($class);
                    $entity = $repo->findEntityByEuidOrUname($uname);
                }
            }
        }
        return $entity instanceof WireEntityInterface ? $entity : null;
    }

    public function getEntitiesCount(
        string $classname,
        array $criteria = []
    ): int
    {
        /** @var BaseWireRepositoryInterface */
        $repository = $this->getRepository($classname);
        return $repository->count($criteria);
    }


    /************************************************************************************************************/
    /** ENTITY INFO                                                                                             */
    /************************************************************************************************************/

    /**
     * Get ClassMetadata for Entity
     * @see https://phpdox.net/demo/Symfony2/classes/Doctrine_ORM_Mapping_ClassMetadata.xhtml
     * 
     * @param string|WireEntityInterface|null $objectOrClass
     * @return ClassMetadata|null
     */
    public function getClassMetadata(
        string|WireEntityInterface $objectOrClass = null,
    ): ?ClassMetadata
    {
        $classname = $objectOrClass instanceof WireEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        return $classname
            ? $this->em->getClassMetadata($classname)
            : null;
    }

    public static function isAppWireEntity(
        string|object $objectOrClass
    ): bool
    {
        return is_object($objectOrClass)
            ? $objectOrClass instanceof WireEntityInterface
            : is_a($objectOrClass, WireEntityInterface::class, true);
    }

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

    public function entityExists(
        string $classname,
        bool $allnamespaces = false,
        bool $onlyInstantiables = false,
    ): bool
    {
        $list = $this->getEntityNames(true, $allnamespaces, $onlyInstantiables);
        return in_array($classname, $list) || array_key_exists($classname, $list);
    }

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
        string|array|null $relationTypes = null,
        bool $excludeSelf = false
    ): array
    {
        $classname = $objectOrClass instanceof WireEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        if(empty($relationTypes)) $relationTypes = null;
        if(is_string($relationTypes)) $relationTypes = [$relationTypes];
        $classnames = [];
        foreach ($this->getEntityNames(false, false, true) as $class) {
            if(!($excludeSelf && is_a($class, $classname, true))) {
                foreach ($this->getClassMetadata($class)->associationMappings as $associationMapping) {
                    $shortname = Objects::getShortname($associationMapping);
                    preg_match('/^((Many|One)To(Many|One))/', $shortname, $types);
                    $type = reset($types);
                    if(!preg_match('/^((Many|One)To(Many|One))$/', $type)) throw new Exception(vsprintf('Error %s line %d: missing %s to class %s, got %s!', [__METHOD__, __LINE__, '((Many|One)To(Many|One))', get_class($associationMapping), $type]));
                    if(is_a($associationMapping->targetEntity, $classname, true) && (empty($relationTypes) || in_array($type, $relationTypes))) {
                        $classnames[$class] = [
                            'mapping_object' => $associationMapping,
                            'mapping_type' => $type,
                        ];
                    }
                }
            }
        }
        return $classnames;
    }


    /************************************************************************************************************/
    /** VICH IMAGE / LIIP IMAGE                                                                                 */
    /************************************************************************************************************/

    public function getBrowserPath(
        WireImageInterface|WirePdfInterface $media,
        string $filter = null,
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