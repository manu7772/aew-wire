<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\EntitiesDescriptorInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\BetweenManyInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Interface\ClassDescriptionInterface;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Service\WireEntityManager;
use Aequation\WireBundle\Tools\Objects;
use Aequation\WireBundle\Tools\Strings;
// Symfony
use Doctrine\ORM\Mapping\ClassMetadata;
// PHP
use Closure;
use Exception;
use ReflectionClass;

/**
 * EntitiesDescriptor
 * 
 * This class is responsible for managing and describing entities in the Wire system.
 * It provides methods to filter, find, and check entities based on various criteria.
 * 
 * finals : - All entities that fullfill 3 conditions:
 *  - have no subclasses
 *  - are instantiable
 *  - are not abstract
 * 
 */
class EntitiesDescriptor implements EntitiesDescriptorInterface
{

    private array $entities = [];
    private bool $filterAppWire = false;
    private bool $filterFinal = false;
    private bool $shortnames = false;

    public function __construct(
        private WireEntityManager $wireEm,
    )
    {
        $this->initialize();
    }


    private function initialize(): void
    {
        $this->entities = [];
        $cheks = [];
        foreach ($this->wireEm->em->getMetadataFactory()->getAllMetadata() as $cmd) {
            /** @var ClassMetadata $cmd */
            $classname = $cmd->getName();
            if(in_array($classname, $cheks)) {
                throw new Exception(vsprintf('Error %s line %d: Class "%s" is already registered in entities descriptor.', [__FILE__, __LINE__, $classname]));
            }
            if(in_array($cmd->reflClass->getShortName(), $cheks)) {
                throw new Exception(vsprintf('Error %s line %d: Class (shortname) "%s" is already registered in entities descriptor. Can not have same shortname twice in Wire system.', [__FILE__, __LINE__, $cmd->reflClass->getShortName()]));
            }
            $cheks[] = $classname;
            $cheks[] = $cmd->reflClass->getShortName();
            $this->entities[$classname] = [
                'classmetadata' => $cmd,
                'classname' => $classname,
                'shortname' => $cmd->reflClass->getShortName(),
                'interfaces' => [],
                'traits' => [],
                'parents' => [],
                'subclasses' => [],
                // 'is_final' => empty($cmd->subClasses) && $cmd->reflClass->isInstantiable(),
                // 'is_abstract' => $cmd->reflClass->isAbstract(),
                // 'is_instantiable' => $cmd->reflClass->isInstantiable(),
            ];
            // Traits
            foreach ($cmd->reflClass->getTraitNames() as $trait) {
                $trait = new ReflectionClass($trait);
                $this->entities[$classname]['traits'][$trait->getName()] = $trait->getShortName();
            }
            // Parent classes & parents traits
            $parent = $cmd->reflClass;
            while ($parent = $parent->getParentClass()) {
                $this->entities[$classname]['parents'][$parent->getName()] = $parent->getShortName();
                foreach ($parent->getTraitNames() as $trait) {
                    $trait = new ReflectionClass($trait);
                    $this->entities[$classname]['traits'][$trait->getName()] = $trait->getShortName();
                }
            }
            // Interfaces
            foreach ($cmd->reflClass->getInterfaces() as $interface) {
                $this->entities[$classname]['interfaces'][$interface->getName()] = $interface->getShortName();
            }
            // Subclasses
            foreach ($cmd->subClasses as $subclass) {
                $subclass = new ReflectionClass($subclass);
                $this->entities[$classname]['subclasses'][$subclass->getName()] = $subclass->getShortName();
            }
        }
    }

    public function getEntities(): array
    {
        return $this->entities;
    }


    public function resetFilters(): static
    {
        $this->enableFilterAppWire(false);
        $this->enableFilterFinal(false);
        $this->enableShortnames(false);
        return $this;
    }

    /************************************************************************************************************/
    /** UTILITIES                                                                                               */
    /************************************************************************************************************/

    public function findClassname(string|object $shortname): ?string
    {
        if(is_object($shortname) || class_exists($shortname)) {
            if(is_object($shortname) && $shortname instanceof ClassDescriptionInterface) {
                return $shortname->getClassname();
            }
            return is_object($shortname) ? $shortname::class : $shortname;
        }
        foreach ($this->entities as $classname => $data) {
            if($data['shortname'] === $shortname) {
                return $classname;
            }
        }
        return null;
    }

    protected function toClassname(
        string|object &$something
    ): void
    {
        $test = $this->findClassname($something);
        if(!$test) {
            /** @var string $something */
            throw new Exception(vsprintf('Error %s line %d: Class "%s" not found in entities descriptor.', [__FILE__, __LINE__, $something]));
        }
        $something = $test;
    }

    public function isEntity(
        string|object $something
    ): bool
    {
        $test = $this->findClassname($something);
        return !empty($test) && isset($this->entities[$test]);
    }

    public function isAbstract(
        string|object $classname
    ): bool
    {
        $test = $this->findClassname($classname);
        if(isset($this->entities[$test])) {
            return $this->entities[$classname]['classmetadata']->reflClass->isAbstract();
        }
        $rc = new ReflectionClass($test);
        return $rc->isAbstract();
    }

    public function getInterfaces(
        string|object $classname
    ): array
    {
        $this->toClassname($classname);
        if(!isset($this->entities[$classname])) {
            throw new Exception(vsprintf('Error %s line %d: Class "%s" not found in entities descriptor.', [__FILE__, __LINE__, $classname]));
        }
        return $this->entities[$classname]['interfaces'];
    }

    public function getTraits(
        string|object $classname
    ): array
    {
        $this->toClassname($classname);
        if(!isset($this->entities[$classname])) {
            throw new Exception(vsprintf('Error %s line %d: Class "%s" not found in entities descriptor.', [__FILE__, __LINE__, $classname]));
        }
        return $this->entities[$classname]['traits'];
    }

    public function getParents(
        string|object $classname
    ): array
    {
        $this->toClassname($classname);
        if(!isset($this->entities[$classname])) {
            throw new Exception(vsprintf('Error %s line %d: Class "%s" not found in entities descriptor.', [__FILE__, __LINE__, $classname]));
        }
        return $this->entities[$classname]['parents'];
    }

    public function getSubclasses(
        string|object $classname
    ): array
    {
        $this->toClassname($classname);
        if(!isset($this->entities[$classname])) {
            throw new Exception(vsprintf('Error %s line %d: Class "%s" not found in entities descriptor.', [__FILE__, __LINE__, $classname]));
        }
        return $this->entities[$classname]['subclasses'];
    }

    /************************************************************************************************************/
    /** FILTER APPWIRE ENTITIES                                                                                 */
    /************************************************************************************************************/

    public function enableFilterAppWire(bool $filter = true): static
    {
        $this->filterAppWire = $filter;
        return $this;
    }

    public function isFilterAppWire(): bool
    {
        return $this->filterAppWire;
    }

    public function filterAppWire(array &$classnames): void
    {
        $classnames = array_filter($classnames, function(string $classname): bool {
            return $this->isAppWireEntity($classname);
        });
    }


    /************************************************************************************************************/
    /** FILTER FINALS INSTANTIABLES                                                                             */
    /************************************************************************************************************/

    public function enableFilterFinal(bool $filter = true): static
    {
        $this->filterFinal = $filter;
        return $this;
    }

    public function isFilterFinal(): bool
    {
        return $this->filterFinal;
    }

    public function filterFinal(array &$classnames): void
    {
        $classnames = array_filter($classnames, function(string $classname): bool {
            return $this->isFinalEntity($classname);
        });
    }


    /************************************************************************************************************/
    /** AS SHORTNAMES                                                                                           */
    /************************************************************************************************************/

    public function enableShortnames(bool $shortnames = true): static
    {
        $this->shortnames = $shortnames;
        return $this;
    }

    public function isShortnames(): bool
    {
        return $this->shortnames;
    }

    public function transformShortnames(array &$classnames): void
    {
        if(empty($classnames)) {
            throw new Exception(vsprintf('Error %s line %d: No classnames to transform to shortnames.', [__FILE__, __LINE__]));
        }
        foreach ($classnames as $classname) {
            if(!class_exists($classname)) {
                throw new Exception(vsprintf('Error %s line %d: Class %s does not exist in array %s.', [__FILE__, __LINE__, json_encode($classname), json_encode($classnames)]));
            }
        }
        $classnames = array_map(fn($class) => Objects::getShortname($class), $classnames);
    }


    /************************************************************************************************************/
    /** TESTS                                                                                                   */
    /************************************************************************************************************/

    /**
     * is AppWire entity
     * - All entities are instance of BaseEntityInterface
     * 
     * @param string|object $objectOrClass
     * @return bool
     */
    public function isAppWireEntity(
        string|object $objectOrClass
    ): bool
    {
        return is_a($objectOrClass, BaseEntityInterface::class, true);
    }

    /**
     * is Between entity
     * - All entities are instance of BetweenManyInterface
     * 
     * @param string|object $objectOrClass
     * @return bool
     */
    public function isBetweenEntity(
        string|object $objectOrClass
    ): bool
    {
        return is_a($objectOrClass, BetweenManyInterface::class, true);
    }

    /**
     * is AppWire entity
     * - All entities are instance of BaseEntityInterface
     * 
     * @param string|object $objectOrClass
     * @return bool
     */
    public function isTranslationEntity(
        string|object $objectOrClass
    ): bool
    {
        return is_a($objectOrClass, WireTranslationInterface::class, true);
    }

    /**
     * is entity instantiable
     * - All entities are instantiable if they are not abstract and have no subclasses
     * 
     * @param string|object $objectOrClass
     * @return bool
     */
    public function isFinalEntity(
        string|object $objectOrClass
    ): bool
    {
        $this->toClassname($objectOrClass);
        if(!$this->wireEm->getClassMetadata($objectOrClass)) {
            throw new Exception(vsprintf('Error %s line %d: %s "%s" is not registered in entities descriptor.', [__FILE__, __LINE__, is_object($objectOrClass) ? 'object' : 'classname', $objectOrClass]));
        }
        return ($cmd = $this->wireEm->getClassMetadata($objectOrClass))
            ? empty($cmd->subClasses) && $cmd->reflClass->isInstantiable() && !$cmd->reflClass->isAbstract()
            : false;
    }


    /************************************************************************************************************/
    /** FIND ALL                                                                                                */
    /************************************************************************************************************/

    public function findAll(null|string|array $names, ?Closure $filter = null, bool $andOperator = true): array
    {
        $classnames = [];
        $names = (array) $names;
        foreach ($this->entities as $class => $data) {
            if(!$filter || $filter($data)) {
                $is = true;
                foreach ($names as $name) {
                    $base_condition =
                        $data['classname'] === $name || $data['shortname'] === $name
                        || in_array($name, $data['interfaces'], true) || array_key_exists($name, $data['interfaces'])
                        || in_array($name, $data['traits'], true) || array_key_exists($name, $data['traits'])
                        || in_array($name, $data['parents'], true) || array_key_exists($name, $data['parents'])
                        ;
                    $is = $andOperator
                        ? $is && $base_condition // AND
                        : $is || $base_condition // OR
                        ;
                    if(!$is) {
                        break; // No need to check further
                    }
                }
                if($is) {
                    $classnames[$class] = $class;
                }
            }
        }
        // Filters...
        if($this->filterAppWire) {
            $this->filterAppWire($classnames);
        }
        if($this->shortnames) {
            $this->transformShortnames($classnames);
        }
        if($this->filterFinal) {
            $this->filterFinal($classnames);
        }
        // Reset filters
        $this->resetFilters();
        return $classnames;
    }


    /************************************************************************************************************/
    /** FIND FINALS                                                                                             */
    /************************************************************************************************************/

    public function findFinals(null|string|array $names, bool $andOperator = true): array
    {
        return $this->enableFilterFinal(true)->findAll($names, andOperator: $andOperator);
        // return $this->findAll($names, fn (array $data): bool => $this->isInstantiableEntity($data['classname']), $andOperator);
    }

    public function findOneFinal(null|string|array $names, bool $andOperator = true): string
    {
        $final = $this->findFinals($names, $andOperator);
        if(count($final) !== 1) {
            throw new Exception(vsprintf('Error %s line %d: Expected exactly one final entity, but found %d.', [__FILE__, __LINE__, count($final)]));            
        }
        return reset($final);
    }

    public function findOneFinalOrNull(null|string|array $names, bool $andOperator = true): ?string
    {
        $finals = $this->findFinals($names, $andOperator);
        return count($finals) === 1 ? reset($finals) : null;
    }


    /************************************************************************************************************/
    /** FIND INSTANTIABLES BY CLASSNAMES/INTERFACES/TRAITS                                                      */
    /************************************************************************************************************/

    public function findInstantiables(null|string|array $names, bool $andOperator = true): array
    {
        return $this->findAll($names, fn (array $data): bool => $data['classmetadata']->reflClass->isInstantiable(), $andOperator);
    }

    public function findOneInstantiable(null|string|array $names, bool $andOperator = true): string
    {
        $instantiables = $this->findInstantiables($names, $andOperator);
        if(count($instantiables) !== 1) {
            throw new Exception(vsprintf('Error %s line %d: Expected exactly one instantiable entity, but found %d.', [__FILE__, __LINE__, count($instantiables)]));
        }
        return reset($instantiables);
    }

    public function findOneInstantiableOrNull(null|string|array $names, bool $andOperator = true): ?string
    {
        $instantiables = $this->findInstantiables($names, $andOperator);
        return count($instantiables) === 1 ? reset($instantiables) : null;
    }

    /************************************************************************************************************/
    /** FIND BY TYPES                                                                                           */
    /************************************************************************************************************/

    public function findHydratableFinals(
        bool $shortnames = false
    ): array
    {
        return $this->enableShortnames($shortnames)->enableFilterFinal(true)->findAll(WireHydratable::class);
    }

    public function findBetweenFinals(
        bool $shortnames = false
    ): array
    {
        return $this->enableShortnames($shortnames)->enableFilterFinal(true)->findAll(BetweenManyInterface::class);
    }

    public function findTranslationFinals(
        bool $shortnames = false
    ): array
    {
        return $this->enableShortnames($shortnames)->enableFilterFinal(true)->findAll(WireTranslationInterface::class);
    }


}