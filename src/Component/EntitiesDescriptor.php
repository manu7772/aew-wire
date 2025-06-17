<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\EntitiesDescriptorInterface;
use Aequation\WireBundle\Service\WireEntityManager;
use Aequation\WireBundle\Tools\Objects;
use Aequation\WireBundle\Tools\Strings;
// Symfony
use Doctrine\ORM\Mapping\ClassMetadata;
// PHP
use Closure;
use Exception;

class EntitiesDescriptor implements EntitiesDescriptorInterface
{

    private array $entities = [];
    private bool $filterAppWire = false;
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
        foreach ($this->wireEm->em->getMetadataFactory()->getAllMetadata() as $cmd) {
            /** @var ClassMetadata $cmd */
            $classname = $cmd->getName();
            $this->entities[$classname] = [
                'classmetadata' => $cmd,
                'classname' => $classname,
                'shortname' => $cmd->reflClass->getShortName(),
                'interfaces' => [],
                'traits' => [],
                'parents' => [],
                'is_final' => empty($cmd->subClasses) && $cmd->reflClass->isInstantiable(),
                'is_abstract' => $cmd->reflClass->isAbstract(),
                'is_instantiable' => $cmd->reflClass->isInstantiable(),
            ];
            // Traits
            foreach ($cmd->reflClass->getTraitNames() as $trait) {
                $this->entities[$classname]['traits'][$trait] = Objects::getShortname($trait);
            }
            // Parent classes
            $parent = $cmd->reflClass;
            while ($parent = $parent->getParentClass()) {
                $this->entities[$classname]['parents'][$parent->getName()] = $parent->getShortName();
                foreach ($parent->getTraitNames() as $trait) {
                    $this->entities[$classname]['traits'][$trait] = Strings::getAfterLast($trait, '\\');
                }
            }
            // Interfaces
            foreach ($cmd->reflClass->getInterfaces() as $interface) {
                $this->entities[$classname]['interfaces'][$interface->getName()] = $interface->getShortName();
            }
        }
    }

    public function getEntities(): array
    {
        return $this->entities;
    }


    /************************************************************************************************************/
    /** FILTER APPWIRE                                                                                          */
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
            return $this->wireEm::isAppWireEntity($classname);
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
        $classnames = array_map(fn($class) => Objects::getShortname($class), $classnames);
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
                    if($andOperator) {
                        // AND
                        $is = $is && (
                            $data['classname'] === $name || $data['shortname'] === $name
                            || in_array($name, $data['interfaces'], true) || array_key_exists($name, $data['interfaces'])
                            || in_array($name, $data['traits'], true) || array_key_exists($name, $data['traits'])
                            || in_array($name, $data['parents'], true) || array_key_exists($name, $data['parents'])
                        );
                    } else {
                        // OR
                        $is = $is || (
                            $data['classname'] === $name || $data['shortname'] === $name
                            || in_array($name, $data['interfaces'], true) || array_key_exists($name, $data['interfaces'])
                            || in_array($name, $data['traits'], true) || array_key_exists($name, $data['traits'])
                            || in_array($name, $data['parents'], true) || array_key_exists($name, $data['parents'])
                        );
                    }
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
        return $classnames;
    }


    /************************************************************************************************************/
    /** FIND FINALS                                                                                             */
    /************************************************************************************************************/

    public function findFinals(null|string|array $names, bool $andOperator = true): array
    {
        return $this->findAll($names, function(array $data): bool {
            return $data['is_final'];
        }, $andOperator);
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
    /** FIND INSTANTIABLES                                                                                      */
    /************************************************************************************************************/

    public function findInstantiables(null|string|array $names, bool $andOperator = true): array
    {
        return $this->findAll($names, function(array $data): bool {
            return $data['is_instantiable'];
        }, $andOperator);
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


}