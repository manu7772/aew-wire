<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Tools\Objects;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\BetweenSortedInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Component\interface\WirePropertyAbstractMetadataInterface;
use Aequation\WireBundle\Dto\interface\WireEntityDtoInterface;
use Aequation\WireBundle\Attribute\WireRelationMapping;
use Aequation\WireBundle\Component\interface\WirePropertyAssociationMetadataInterface;
use Aequation\WireBundle\Component\interface\WirePropertyFieldMetadataInterface;
use Aequation\WireBundle\Dto\BaseDto;
use Aequation\WireBundle\Service\interface\HydrationServiceInterface;
// Symfony
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use Symfony\Component\ObjectMapper\Attribute\Map;
// PHP
use Exception;
use ReflectionClass;
use InvalidArgumentException;

class WireClassMetadata implements WireClassMetadataInterface
{
    public readonly ReflectionClass $reflectionClass;
    public readonly ?ClassMetadata $classMetadata;
    public readonly HydrationServiceInterface $HydrationService;
    public readonly false|WireEntityServiceInterface $service;
    public readonly false|EntityRepository $repository;
    protected array $properties = ['fields' => [], 'relations' => []];
    protected false|array $relationNames = false;
    public readonly bool $isDev;
    // Data
    public readonly string $name;
    protected readonly array $parents;
    protected readonly array $subClasses;
    protected readonly array $interfaces;
    protected readonly array $traits;
    protected readonly false|WireRelationMapping $virtualRelationMapping;
    protected readonly array $wireRelationMapping;

    public function __construct(
        public readonly WireClassMetadataManagerInterface $wCmdm,
        string|ClassMetadata $classUtil,
    ) {
        $this->isDev = $this->wCmdm->isDev;
        $this->HydrationService = $this->wCmdm->wireEm->getHydrationService();
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
        $this->getVirtualRelationMapping();
        $this->getSubclasses();
    }


    /************************************************************************************************************/
    /** BASE INFOS                                                                                              */
    /************************************************************************************************************/

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getWireClassMetadataManager(): WireClassMetadataManagerInterface
    {
        return $this->wCmdm;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShortName(): string
    {
        return $this->reflectionClass->getShortName();
    }

    public function getClassMetadata(): ?ClassMetadata
    {
        return $this->classMetadata;
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
        return isset($this->$name) || isset($this->classMetadata->$name);
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

    public function getFirstNextManagedSubclass(): ?WireClassMetadataInterface
    {
        $subs = $this->getSubclasses(true);
        return empty($subs) ? null : reset($subs);
    }

    public function getNextUniqueManagedSubclass(): ?WireClassMetadataInterface
    {
        $subs = $this->getSubclasses(true);
        return count($subs) !== 1 ? null : reset($subs);
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

    public function newDto(array $data = [], array $options = []): ?WireEntityDtoInterface
    {
        $dto_class = $options['dto_class'] ?? $this->getFirstDtoSourceMap()?->source ?? null;
        if($dto_class) {
            return is_a($dto_class, WireEntityDtoInterface::class, true)
                ? new $dto_class($data, $this->wCmdm->wireEm, $options)
                : new $dto_class();
        }
        return null;
    }


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

    public function isHydrationAvailable(): bool
    {
        return $this->isType('serializable') && $this->HydrationService->getHydatableData();
    }


    /************************************************************************************************************/
    /** ENTITY MANAGER UTILITIES                                                                                */
    /************************************************************************************************************/

    public function getHydrationService(): HydrationServiceInterface
    {
        return $this->wCmdm->wireEm->getHydrationService();
    }

    public function getService(): false|WireEntityServiceInterface
    {
        if(!isset($this->service)) {
            // $list = array_merge([$this], $this->getParents());
            $list = [$this];
            foreach ($list as $self) {
                $service = $this->wCmdm->wireEm->appWire->getClassService($self->name);
                if($service instanceof WireEntityServiceInterface) {
                    $this->service = $service;
                    break;
                }
            }
            if(!isset($this->service)) {
                if($subclass = $this->getNextUniqueManagedSubclass()) {
                    $this->service = $subclass->getService();
                }
            }
            $this->service ??= false;
        }
        return $this->service;
    }

    public function getRepository(): false|EntityRepository
    {
        if(!isset($this->repository)) {
            // $list = array_merge([$this], $this->getParents());
            $list = [$this];
            foreach ($list as $self) {
                try {
                    // If managed, get repository from class metadata
                    $this->repository = $this->wCmdm->wireEm->getEm()->getRepository($self->name);
                } catch (Exception $e) {
                    // If not managed, try to get repository from managed parent class
                }
                if(isset($this->repository)) {
                    break;
                }
            }
            if(!isset($this->repository)) {
                if($subclass = $this->getNextUniqueManagedSubclass()) {
                    $this->repository = $subclass->getRepository();
                }
            }
            $this->repository ??= false;
        }
        return $this->repository;
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
    /** ATTRIBUTES                                                                                              */
    /************************************************************************************************************/

    public function getClassAttributes(string $name): mixed
    {
        $attrs = Objects::getClassAttributes($this->reflectionClass, $name);
        // If not found, return null
        return empty($attrs) ? null : $attrs;
    }


    /************************************************************************************************************/
    /** FIELDS/RELATIONS                                                                                        */
    /************************************************************************************************************/

    public function getProperty(string $name): ?WirePropertyAbstractMetadataInterface
    {
        return $this->getField($name) ?? $this->getRelation($name) ?? null;
    }

    public function getField(string $name): ?WirePropertyFieldMetadataInterface
    {
        $parts = preg_split('/\./', $name);
        $property = array_shift($parts);
        $test = $property.'['.implode('][', $parts).']';
        if(!isset($this->properties['fields'][$property]) && in_array($name, $this->getFieldNames())) {
            $test .= ' => TRUE';
            if(isset($this->properties['fields'][$property])) $test = false;
            $wpm = new WirePropertyFieldMetadata($this->name, $property, $this, $parts);
            return $this->properties['fields'][$wpm->name] = $wpm;
        } else {
            $test .= ' => FALSE';
            if(isset($this->properties['fields'][$property])) $test = false;
        }
        if($test && $this->getShortName() === 'Webpage') dump($test);
        return $this->properties['fields'][$property] ??null;
    }

    public function getFieldNames(bool $includeVirtuals = true): array
    {
        return array_combine($this->classMetadata->getFieldNames(), $this->classMetadata->getFieldNames()) ?: [];
    }

    public function getFields(bool $includeVirtuals = true): array
    {
        foreach ($this->getFieldNames() as $name) {
            $this->getField($name);
        }
        return $includeVirtuals ? $this->properties['fields'] : array_filter($this->properties['fields'], fn (WirePropertyFieldMetadataInterface $wpm) => !$wpm->isVirtualChild());
    }

    public function getRelation(string $name): ?WirePropertyAssociationMetadataInterface
    {
        if(count(preg_split('/\./', $name)) > 1) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: relation name "%s" cannot contain dots.', [__METHOD__, __LINE__, $name]));
        }
        if(!isset($this->properties['relations'][$name]) && in_array($name, $this->getRelationNames(true))) {
            $wpm = new WirePropertyAssociationMetadata($this->name, $name, $this);
            $this->properties['relations'][$wpm->name] = $wpm;
            if($wpm->hasVirtualChilds()) {
                // Add virtual relations
                foreach ($wpm->getVirtualChilds() as $subname => $rel) {
                    $this->properties['relations'][$subname] = $rel;
                }
            }
        }
        return $this->properties['relations'][$name] ?? null;
    }

    public function getRelationNames(bool $includeVirtuals = true): array
    {
        if(false === $this->relationNames) {
            $this->relationNames = [];
            foreach ($this->classMetadata->getAssociationNames() as $fieldname) {
                $this->relationNames[$fieldname] = $fieldname;
                // If the relation is a virtual relation, add the virtual names
                if($this->virtualRelationMapping) {
                    foreach ($this->virtualRelationMapping->getFieldPropertyNames($fieldname) as $property) {
                        if(!array_key_exists($property, $this->relationNames)) {
                            $this->relationNames['@'.$property] = $property; // Prefix key with '@' to indicate virtual relation
                        }
                    }
                }
            }
        }
        // if(str_ends_with($this->getShortName(), "Factory")) dump(array_merge(['_name' => $this->name], $this->relationNames));
        return $includeVirtuals ? $this->relationNames : array_filter($this->relationNames, fn ($keyname) => !str_starts_with($keyname, '@'), ARRAY_FILTER_USE_KEY);
    }

    public function getRelations(bool $includeVirtuals = true): array
    {
        foreach ($this->getRelationNames($includeVirtuals) as $name) {
            $this->getRelation($name);
        }
        return $includeVirtuals ? $this->properties['relations'] : array_filter($this->properties['relations'], fn (WirePropertyAssociationMetadataInterface $wpm) => !$wpm->isVirtualChild());
    }


    /************************************************************************************************************/
    /** FIELDS/RELATIONS --> Targets                                                                            */
    /************************************************************************************************************/

    public function getTarget(string $name, bool $findOutVirtual = true): WireClassMetadataInterface
    {
        if($relation = $this->getRelation($name)) {
            return $relation->getTarget($findOutVirtual);
            // $target = $this->wCmdm->getWireClassMetadata($this->getTargetName($name));
            // if($target->isBetween()) {
            //     // If the target is a BetweenSortedInterface, we return the final names of the child classes
            //     foreach ($target->getAssociationMappings() as $mapping) {
            //         if($mapping->inversedBy !== $name) {
            //             return $this->wCmdm->getWireClassMetadata($mapping->targetEntity);
            //         }
            //     }
            // }
        }
        throw new InvalidArgumentException(vsprintf('Error %s line %d: property %s is not a relation in class %s.', [__METHOD__, __LINE__, $name, $this->name]));
    }

    public function getTargetName(string $name): false|string
    {
        if(!$this->isManaged()) {
            if($next = $this->getFirstNextManagedSubclass()) {
                return $next->getTargetName($name);
            }
            // Try find out next managed subclass
            // foreach ($this->getSubclasses(true) as $wCmd) {
            //     if($target = $wCmd->getTargetName($name)) {
            //         return $target;
            //     }
            // }
            throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not managed by Doctrine, cannot get target name for relation %s.', [__METHOD__, __LINE__, $this->name, $name]));
        }
        return $this->classMetadata->getAssociationMapping($name)->targetEntity;
    }

    public function getTargetNames(string $name, string $type = 'final'): false|array
    {
        if($property = $this->getRelation($name)) {
            return $property->getTargetNames($type);
        }
        return false;
    }


    /************************************************************************************************************/
    /** FIELDS/RELATIONS --> OrphanRemoval                                                                      */
    /************************************************************************************************************/

    /**
     * Get OrphanRemoval associations
     * Returns the array<WirePropertyAssociationMetadataInterface> relations of the class that are marked as orphanRemoval
     *
     * @param string $relation
     * @return array<WirePropertyAssociationMetadataInterface>
     */
    public function getOrphanRelations(): array
    {
        return array_filter(
            $this->getRelations(false),
            fn (WirePropertyAssociationMetadataInterface $wpm) => $wpm->orphanRemoval
        );
    }


    /************************************************************************************************************/
    /** VIRTUAL RELATION MAPPING                                                                               */
    /************************************************************************************************************/

    /**
     * Get virtual relation data
     * Returns properties data of relations not defined in the class metadata
     *
     * @param string|null $name
     * @return false|WireRelationMapping
     */
    public function getVirtualRelationMapping(): false|WireRelationMapping
    {
        if(!isset($this->virtualRelationMapping)) {
            $mappings = Objects::getClassAttributes($this->name, WireRelationMapping::class);
            $this->virtualRelationMapping = empty($mappings) ? false : reset($mappings);
            // if(defined($this->name.'::ITEMS_ACCEPT')) {
            //     $mappings = new WireRelationMapping($this->name::ITEMS_ACCEPT);
            //     /** @var WireRelationMapping $mapping */
            //     $this->virtualRelationMapping = $mappings->mapping;
            //     dump($this->name, $this->virtualRelationMapping);
            // } else {
            //     $this->virtualRelationMapping = [];
            // }
        }
        // if(empty($this->virtualRelationMapping)) {
        //     return false;
        // }
        return $this->virtualRelationMapping;
    }

}