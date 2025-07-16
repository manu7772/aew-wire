<?php
namespace Aequation\WireBundle\Component;

use Closure;
use Exception;
use ReflectionClass;
use BadMethodCallException;
use InvalidArgumentException;
use Doctrine\ORM\EntityRepository;
use Aequation\WireBundle\Tools\Objects;
use Doctrine\ORM\Mapping\ClassMetadata;
// Symfony
use Doctrine\ORM\Mapping\AssociationMapping;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Attribute\WireRelationMapping;
// PHP
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\BetweenManyInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Component\interface\WirePropertyMetadataInterface;
use Aequation\WireBundle\Dto\interfaace\WireEntityDtoInterface;
use Aequation\WireBundle\Dto\WireFactoryDto;
use Symfony\Component\ObjectMapper\Attribute\Map;

class WireClassMetadata implements WireClassMetadataInterface
{
    public const SERIALIZATION_MAPPINGS_BY_ATTRIBUTE = true;

    public readonly ReflectionClass $reflectionClass;
    public readonly ?ClassMetadata $classMetadata;
    public readonly ?WireEntityServiceInterface $service;
    public readonly ?EntityRepository $repository;
    protected array $properties = [];
    public readonly bool $isDev;
    // Data
    public readonly string $name;
    protected readonly array $parents;
    protected readonly array $subClasses;
    protected readonly array $interfaces;
    protected readonly array $traits;
    protected readonly array $wireRelationData;
    protected readonly array $wireRelationMapping;

    public function __construct(
        public readonly WireClassMetadataManagerInterface $wCmdm,
        string|ClassMetadata $classUtil,
    ) {
        $this->isDev = $this->wCmdm->isDev;
        if($classUtil instanceof ClassMetadata) {
            $this->classMetadata = $classUtil;
            $this->reflectionClass = $this->classMetadata->getReflectionClass();
            $this->name = $this->classMetadata->getName();
        } elseif(is_string($classUtil)) {
            $this->classMetadata = null;
            $this->reflectionClass = new ReflectionClass($classUtil);
            $this->name = $this->reflectionClass->getName();
            // $this->wCmdm->registerWireClassMetadata($this);
        }
        $this->getSubclasses();
    }


    /************************************************************************************************************/
    /** BASE INFOS                                                                                              */
    /************************************************************************************************************/

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShortName(): string
    {
        return $this->reflectionClass->getShortName();
    }

    public function getReflectionClass(): ReflectionClass
    {
        return $this->reflectionClass;
    }

    public function isFinal(): bool
    {
        return
            $this->isManaged()
            && empty($this->getSubclasses())
            && $this->reflectionClass->isInstantiable()
            ;
    }

    public function isInstantiable(): bool
    {
        return
            $this->isManaged()
            && $this->reflectionClass->isInstantiable()
            ;
    }

    public function isManaged(): bool
    {
        return $this->classMetadata instanceof ClassMetadataInterface;
    }

    public function isAbstract(): bool
    {
        return $this->reflectionClass->isAbstract();
    }

    public function getInfo(): array
    {
        return [
            'name' => $this->getName(),
            'shortname' => $this->getShortName(),
            'isFinal' => $this->isFinal(),
            'isAbstract' => $this->isAbstract(),
            'isManaged' => $this->isManaged(),
            // hierarchy
            'parentManaged' => $this->getParentManaged()?->getName() ?: null,
            'parent' => $this->getParentName(),
            'parents' => $this->getParentsNames(),
            'interfaces' => $this->getInterfacesNames(),
            'traits' => $this->getTraitsNames(),
            'subclasses' => $this->getSubclassesNames(),
            // tests
            'isAppwire' => $this->isAppwire(),
            'isBetween' => $this->isBetween(),
            'isTranslation' => $this->isTranslation(),
            'isHydratable' => $this->isHydratable(),
        ];
    }


    /************************************************************************************************************/
    /** INHERITED FROM CLASSMETADATA                                                                            */
    /************************************************************************************************************/

    public function __call($name, $arguments)
    {
        if(!method_exists($this, $name)) {
            return $this->classMetadata->$name(...$arguments);
        }
    }

    public function __get($name)
    {
        return $this->$name ?? $this->classMetadata->$name;
    }

    public function __isset($name)
    {
        return isset($this->classMetadata->$name);
    }


    /************************************************************************************************************/
    /** HIERARCHY                                                                                               */
    /************************************************************************************************************/

    /**
     * Get parent class wiremetadata
     * Returns null if no parent class metadata is found
     *
     * @return static|null
     */
    public function getParent(): ?static
    {
        if($parent = $this->reflectionClass->getParentClass()) {
            return $this->wCmdm->getWireClassMetadata($parent->getName()) ?? null;
        }
        return null;
    }

    /**
     * Get parent class entity metadata MANAGED BY DOCTRINE
     * Returns null if no parent class entity metadata is found
     *
     * @return static|null
     */
    public function getParentManaged(): ?static
    {
        if($this->isManaged()) {
            $parents = $this->classMetadata->parentClasses;
            return count($parents) ? $this->wCmdm->getWireClassMetadata(reset($parents)) : null;
        }
        // If not managed, check subclasses for parent
        foreach ($this->getSubclasses() as $wCmd) {
            if($parent = $wCmd->getParentManaged()) {
                return $parent;
            }
        }
        return null;
    }

    public function getParentName(): ?string
    {
        return $this->getParent()?->getName() ?: null;
    }

    /**
     * Get all parents of the class
     */
    public function getParents(): array
    {
        if(!isset($this->parents)) {
            $parents = [];
            $parent = $this;
            while ($parent = $parent->getParent()) {
                $parents[$parent->getName()] = $parent;
            }
            $this->parents = $parents;
        }
        return $this->parents;
    }

    public function getParentsNames(): array
    {
        if($this->wCmdm->isInitialized()) {
            return array_map(
                fn(WireClassMetadataInterface $parent) => $parent->getShortName(),
                $this->getParents()
            );
        }
        // Before initialization, use reflection to get parent names
        $names = [];
        $parent = $this->reflectionClass->getParentClass();
        while ($parent) {
            $names[$parent->getName()] = $parent->getShortName();
            $parent = $parent->getParentClass();
        }
        return $names;
    }

    public function getSubclasses(bool $onlyManaged = false): array
    {
        if(!isset($this->subClasses)) {
            $subClasses = [];
            // $subs = $this->classMetadata?->subClasses ?: Objects::getSubclasses($this->getName());
            $subs = Objects::getSubclasses($this->getName());
            foreach ($subs as $subClass) {
                if($subclass = $this->wCmdm->getWireClassMetadata($subClass)) {
                    $subClasses[$subclass->getName()] = $subclass;
                } else {
                    // throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not managed by Doctrine, cannot get subclass metadata.', [__METHOD__, __LINE__, $subClass]));
                }
            }
            $this->subClasses = $subClasses;
        }
        return $onlyManaged ? array_filter($this->subClasses, fn ($sub) => $sub->isManaged()) : $this->subClasses;
    }

    public function getSubclassesNames(bool $onlyManaged = false): array
    {
        return array_map(
            fn(WireClassMetadataInterface $subclass) => $subclass->getShortName(),
            $this->getSubclasses($onlyManaged)
        );
    }

    public function getInterfaces(): array
    {
        if(!isset($this->interfaces)) {
            $interfaces = [];
            foreach ($this->reflectionClass->getInterfaces() as $interface) {
                $interfaces[$interface->getName()] = $interface;
            }
            $this->interfaces = $interfaces;
        }
        return $this->interfaces;
    }

    public function implementsInterfaces(array $interfaces, bool $typeCompareAnd = true): bool
    {
        if(empty($interfaces)) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: interfaces array cannot be empty.', [__METHOD__, __LINE__]));
        }
        if($typeCompareAnd) {
            // Compare interfaces with AND operator
            foreach ($interfaces as $interface) {
                // dump(vsprintf('- Checking AND interface %s in class %s => %s', [$interface, $this->getName(), json_encode($this->reflectionClass->implementsInterface($interface))]));
                // if(!$this->reflectionClass->implementsInterface($interface)) {
                if(!is_a($this->getName(), $interface, true)) {
                    return false;
                }
            }
            return true;
        }
        // Compare interfaces with OR operator
        foreach ($interfaces as $interface) {
            // dump(vsprintf('- Checking OR interface %s in class %s => %s', [$interface, $this->getName(), json_encode($this->reflectionClass->implementsInterface($interface))]));
            // if($this->reflectionClass->implementsInterface($interface)) {
            if(is_a($this->getName(), $interface, true)) {
                return true;
            }
        }
        return false;
    }

    public function getInterfacesNames(): array
    {
        return array_map(
            fn(ReflectionClass $interface) => $interface->getShortName(),
            $this->getInterfaces()
        );
    }

    public function getTraits(): array
    {
        if(!isset($this->traits)) {
            $traits = [];
            foreach ($this->reflectionClass->getTraitNames() as $trait) {
                $trait = new ReflectionClass($trait);
                $traits[$trait->getName()] = $trait;
            }
            // Parents traits
            if($parent = $this->getParent()) {
                foreach ($parent->getTraits() as $trait) {
                    $traits[$trait->getName()] = $trait;
                }
            }
            $this->traits = $traits;
        }
        return $this->traits;
    }

    public function getTraitsNames(): array
    {
        return array_map(
            fn(ReflectionClass $trait) => $trait->getShortName(),
            $this->getTraits()
        );
    }


    /************************************************************************************************************/
    /** NEW INSTANCE                                                                                            */
    /************************************************************************************************************/

    public function newInstance(?array $data = null, array $context = []): object
    {
        if($this->isInstantiable()) {
            return $this->reflectionClass->newInstance($data);
        }
        throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not final, cannot create a new instance.', [__METHOD__, __LINE__, $this->name]));
    }

    public function newModel(?array $data = null, array $context = []): object
    {
        if($this->isInstantiable()) {
            $entity = $this->reflectionClass->newInstance($data);
            if($entity instanceof WireEntityInterface) {
                $entity->getSelfState()->setModel();
                return $entity;
            }
            throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not an instance of %s, cannot create a new model.', [__METHOD__, __LINE__, $this->name, WireEntityInterface::class]));
        }
        throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not final, cannot create a new model.', [__METHOD__, __LINE__, $this->name]));
    }

    // public function newDto(array $data = [], array $context = []): WireEntityDtoInterface
    // {
    //     $dto = new WireFactoryDto($data, $context);
    //     return $dto;
    // }


    /************************************************************************************************************/
    /** TYPES                                                                                                   */
    /************************************************************************************************************/

    public function isType(string $type): bool
    {
        foreach (WireClassMetadataManager::ENTITY_TYPES[$type] as $interface) {
            if(!$this->reflectionClass->implementsInterface($interface)) {
                return false;
            }
        }
        return true;
    }

    public function isAppwire(): bool
    {
        return $this->isType('appwire');
    }

    public function isBetween(): bool
    {
        return $this->isType('between');
    }

    public function isTranslation(): bool
    {
        return $this->isType('translation');
    }

    public function isHydratable(): bool
    {
        return $this->isType('hydratable');
    }


    /************************************************************************************************************/
    /** ENTITY MANAGER UTILITIES                                                                                */
    /************************************************************************************************************/

    public function getService(): ?WireEntityServiceInterface
    {
        if(!isset($this->service)) {
            $service = $this->wCmdm->wireEm->appWire->getClassService($this->name);
            return $service instanceof WireEntityServiceInterface
                ? $this->service = $service
                : null;
        }
        return $this->service;
    }

    public function getRepository(): EntityRepository
    {
        if(!isset($this->repository)) {
            try {
                // If managed, get repository from class metadata
                $this->repository = $this->wCmdm->wireEm->getEm()->getRepository($this->name);
            } catch (Exception $e) {
                // If not managed, try to get repository from managed subclass
                throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s cannot find repository.%s%s', [__METHOD__, __LINE__, $this->name, PHP_EOL, $e->getMessage()]));
                // $this->repository = $this->wCmdm->findOneManaged([$this->name])->getRepository();
            }
        }
        if($this->repository instanceof EntityRepository) {
            return $this->repository;
        }
        throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not managed by Doctrine, cannot get repository.', [__METHOD__, __LINE__, $this->name]));
    }

    public function getDtoSourceMaps(): array
    {
        return Objects::getDtoSourceMaps(
            $this->name,
            fn (Map $map): bool => !is_a($this->name, WireEntityInterface::class, true) || is_a($map->source, WireEntityDtoInterface::class, true)
        );
    }

    public function getFirstDtoSourceMap(): ?Map
    {
        $maps = $this->getDtoSourceMaps();
        return reset($maps) ?: null;
    }

    public function getDtoTargetMaps(): array
    {
        $maps = [];
        foreach ($this->getDtoSourceMaps() as $map) {
            $maps = array_merge($maps, Objects::getDtoTargetMaps(
                    $map->source,
                    fn (Map $map): bool => !is_a($map->source, WireEntityInterface::class, true) || is_a($map->target, WireEntityDtoInterface::class, true)
                ));
        }
        return $maps;
    }

    public function getFirstDtoTargetMap(): ?Map
    {
        $maps = $this->getDtoTargetMaps();
        return reset($maps) ?: null;
    }


    /************************************************************************************************************/
    /** FIELDS/RELATIONS                                                                                        */
    /************************************************************************************************************/

    public function getProperty(string $name): ?WirePropertyMetadataInterface
    {
        if(isset($this->properties[$name])) {
            return $this->properties[$name];
        }
        if($this->reflectionClass->hasProperty($name)) {
            $property = $this->reflectionClass->getProperty($name);
            return $this->properties[$name] = new WirePropertyMetadata($property, $this);
        }
        return null;
    }

    public function getTarget(string $relation): WireClassMetadataInterface
    {
        if($this->getProperty($relation)->isRelation()) {
            $target = $this->wCmdm->getWireClassMetadata($this->getTargetName($relation));
            if($target->isBetween()) {
                // If the target is a BetweenManyInterface, we return the final names of the child classes
                foreach ($target->getAssociationMappings() as $mapping) {
                    if($mapping->inversedBy !== $relation) {
                        break;
                    }
                }
                $target = $this->wCmdm->getWireClassMetadata($mapping->targetEntity);
            }
            return $target;
        }
        throw new InvalidArgumentException(vsprintf('Error %s line %d: property %s is not a relation in class %s.', [__METHOD__, __LINE__, $relation, $this->name]));
    }

    public function getTargetName(string $relation): string
    {
        if(!$this->isManaged()) {
            // Try find next managed subclass
            foreach ($this->getSubclasses() as $wCmd) {
                if($wCmd->isManaged()) {
                    return $wCmd->getTargetName($relation);
                }
            }
            throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not managed by Doctrine, cannot get target name for relation %s.', [__METHOD__, __LINE__, $this->name, $relation]));
        }
        return $this->getAssociationMapping($relation)->targetEntity;
    }

    public function getTargetNames(string $relation, string $type = 'final'): array
    {
        $target = $this->getTarget($relation);
        // Check by WireRelationMapping Attribute data if exists
        $values = null;
        foreach ($this->getRelativeAssociationData() as $prop => $values) {
            if($relation === $values['field']) {
                break;
            }
        }
        if(!empty($values)) {
            return $this->wCmdm
                ->setSearchMode($type)
                ->filterClasses($values['require'])
                ->mapSingleValue('shortname')
                ;
        }
        return $this->wCmdm
            ->setSearchMode($type)
            ->filterClasses([$target->name])
            ->mapSingleValue('shortname')
            ;
    }

    /**
     * Get OrphanRemoval associations
     * Returns the WirePropertyMetadatas relations of the class that are marked as orphanRemoval
     *
     * @param string $relation
     * @return AssociationMapping
     */
    public function getOrphanRelations(): array
    {
        $relative_mappings = $this->getRelativeAssociationMappings(true);
        // Filter orphan relations
        $filtered_mappings = [];
        foreach ($this->classMetadata->getAssociationMappings() as $mapping) {
            if(is_a($mapping->targetEntity, BetweenManyInterface::class, true)) {
                // If the target entity is a BetweenManyInterface, we return the final names of the child classes
                $relative =  $relative_mappings[$mapping->fieldName] ?? null;
                if($relative && $relative->orphanRemoval) {
                    // If the mapping is found in relative mappings, we keep it
                    $filtered_mappings[$relative->name] ??= $relative;
                }
            } else if($mapping->orphanRemoval) {
                $filtered_mappings[$mapping->fieldName] ??= $this->getProperty($mapping->fieldName);
            }
        }
        return $filtered_mappings;
    }

    /**
     * Get relative relation mappings
     * Returns mappings of relations not defined in the class metadata
     *
     * @return array
     */
    protected function getRelativeAssociationMappings(): array
    {
        if(!isset($this->wireRelationMapping)) {
            $mappings = [];
            foreach ($this->getRelativeAssociationData() as $data) {
                $mappings[$data['field']] ??= $this->getProperty($data['field']);
            }
            $this->wireRelationMapping = $mappings;
        }
        return $this->wireRelationMapping;
    }

    /**
     * Get relative relation data
     * Returns properties data of relations not defined in the class metadata
     *
     * @return array
     */
    protected function getRelativeAssociationData(): array
    {
        if(!isset($this->wireRelationData)) {
            if(static::SERIALIZATION_MAPPINGS_BY_ATTRIBUTE) {
                // Get serialization mappings by WireRelationMapping attribute
                $mappings = Objects::getClassAttributes($this->name, WireRelationMapping::class);
                // Get first mapping
                /** @var WireRelationMapping|false $mapping */
                $mappings = reset($mappings);
            } else {
                // Get serialization mappings by class constant ITEMS_ACCEPT
                $constant = $this->name.'::ITEMS_ACCEPT';
                $mappings = defined($constant) ? constant($constant) : [];
                $mappings = new WireRelationMapping($mappings);
            }
            $this->wireRelationData = $mappings instanceof WireRelationMapping ? $mappings->getMapping() : [];
        }
        return $this->wireRelationData;
    }

}