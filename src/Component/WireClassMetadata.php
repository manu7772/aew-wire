<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Attribute\WireRelationMapping;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Entity\interface\BetweenManyInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
// PHP
use ReflectionClass;
use BadMethodCallException;
use Closure;
use InvalidArgumentException;

class WireClassMetadata implements WireClassMetadataInterface
{
    public const SERIALIZATION_MAPPINGS_BY_ATTRIBUTE = true;

    public readonly ?ClassMetadata $classMetadata;
    public readonly ReflectionClass $reflectionClass;
    public readonly bool $isDev;
    // Data
    public readonly string $name;
    protected readonly array $parents;
    protected readonly array $subClasses;
    protected readonly array $interfaces;
    protected readonly array $traits;
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
    }


    /************************************************************************************************************/
    /** BASE INFOS                                                                                              */
    /************************************************************************************************************/

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
        // if(method_exists($this->classMetadata, $name)) {
            return $this->classMetadata->$name(...$arguments);
        // }
        // throw new BadMethodCallException(vsprintf('Error %s line %d: method %s does not exist in class %s neither in %s', [__METHOD__, __LINE__, $name, static::class, ClassMetadata::class]));
    }

    public function __get($name)
    {
        // if($this->__isset($name)) {
            return $this->classMetadata->$name;
        // }
        // throw new BadMethodCallException(vsprintf('Error %s line %d: property %s does not exist in class %s neither in %s', [__METHOD__, __LINE__, $name, static::class, ClassMetadata::class]));
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

    public function getSubclasses(): array
    {
        if(!isset($this->subClasses)) {
            $subClasses = [];
            $subs = $this->classMetadata?->subClasses ?: Objects::getSubclasses($this->getName());
            foreach ($subs as $subClass) {
                $subclass = $this->wCmdm->getWireClassMetadata($subClass);
                $subClasses[$subclass->getName()] = $subclass;
            }
            $this->subClasses = $subClasses;
        }
        return $this->subClasses;
    }

    public function getSubclassesNames(): array
    {
        return array_map(
            fn(WireClassMetadataInterface $subclass) => $subclass->getShortName(),
            $this->getSubclasses()
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
    /** TESTS                                                                                                   */
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
    /** ASSOCIATION MAPPING                                                                                     */
    /************************************************************************************************************/

    public function getTarget(string $relation): WireClassMetadataInterface
    {
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

    public function getTargetName(string $relation): string
    {
        if(!$this->isManaged()) {
            // Try find next managed subclass
            foreach ($this->getSubclasses() as $wCmd) {
                if($wCmd->isManaged()) {
                    return $wCmd->getTargetName($relation);
                }
            }
            throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not managed by Doctrine, cannot get target name for relation %s.', [__METHOD__, __LINE__, $this->getName(), $relation]));
        }
        return $this->classMetadata->getAssociationMapping($relation)->targetEntity;
    }

    public function getTargetFinalNames(string $relation): array
    {
        $target = $this->getTarget($relation);
        // Check by WireRelationMapping Attribute data if exists
        $values = null;
        foreach ($this->getRelativeAssociationMappings() as $prop => $values) {
            if($relation === $values['field']) {
                break;
            }
        }
        if(!empty($values)) {
            return $this->wCmdm
                ->setSearchMode('final')
                ->filterClasses($values['require'])
                ->mapSingleValue('shortname')
                ;
        }
        return $this->wCmdm
            ->setSearchMode('final')
            ->filterClasses([$target->name])
            ->mapSingleValue('shortname')
            ;
    }

    /**
     * Get relative relation mappings
     * Returns properties of relations not defined in the class metadata
     *
     * @return array
     */
    protected function getRelativeAssociationMappings(): array
    {
        if(!isset($this->wireRelationMapping)) {
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
            $this->wireRelationMapping = $mappings instanceof WireRelationMapping ? $mappings->getMapping() : [];
        }
        return $this->wireRelationMapping;
    }

}