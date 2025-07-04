<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\WireClassMetadataCollectionInterface;
use Aequation\WireBundle\Service\WireEntityManager;
use Aequation\WireBundle\Interface\ClassDescriptionInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\BetweenManyInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Entity\BaseMappSuperClassEntity;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\MappSuperClassEntity;
use Aequation\WireBundle\Entity\WireEcollection;
use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Entity\WireWebpage;
use Aequation\WireBundle\Interface\WireHydratable;
use Closure;
// Symfony
use Doctrine\ORM\EntityRepository;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Stopwatch\Stopwatch;
// PHP
use Exception;
use Throwable;
use ReflectionClass;
use InvalidArgumentException;

class WireClassMetadataManager implements WireClassMetadataManagerInterface
{
    public const ENTITY_TYPES = [
        'all' => [],
        'appwire' => [WireEntityInterface::class],
        'between' => [BetweenManyInterface::class],
        'translation' => [WireTranslationInterface::class],
        'hydratable' => [WireHydratable::class],
    ];
    public const SEARCH_MODES = [
        'all',          // All entities
        'final',        // Final entities only (so managed entities that are not abstract and have no subclasses)
        'managed',      // Managed by EntityManager/Doctrine entities
        'abstract',     // Abstract entities only
    ];
    const STOPWATCH_NAME = 'WireClassMetadataManager::initialize';

    public readonly WireClassMetadataCollectionInterface $allClassMetadatas;
    protected string $searchMode;
    protected bool $typeCompare;
    public readonly bool $isDev;
    public readonly bool $isDevOrSadmin;
    public readonly PropertyAccessorInterface $accessor;
    private readonly Stopwatch $stopwatch;
    private bool $initialized = false;

    public function __construct(
        public readonly WireEntityManager $wireEm,
    )
    {
        $this->stopwatch = new Stopwatch(true);
        $this->stopwatch->start(static::STOPWATCH_NAME);
        $this->isDev = $this->wireEm->isDev();
        $this->isDevOrSadmin = $this->wireEm->appWire->isDevOrSadmin();
        $this->accessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidPropertyPath()->getPropertyAccessor();
        $this->resetFilters();
        $this->initialize();
        if($this->isDevOrSadmin) {
            $event = $this->stopwatch->stop(static::STOPWATCH_NAME);
            if($event->getDuration() >= 40) {
                // If the initialization took more than 40ms, we log a warning
                $message = vsprintf('%s line %d: [DEV] WireClassMetadataManager initialized in %d ms', [__METHOD__, __LINE__, $event->getDuration()]);
                $this->wireEm->logger->warning($message);
                $this->wireEm->appWire->addFlash('warning', $message);
                // throw new Exception($message);
            }
            // *** Some dev tests ***
            // $interfaces = [WireEcollectionInterface::class];
            // dump($this->setSearchMode('managed')->filterClasses($interfaces)->mapSingleValue('final'));
            // dump($this->setSearchMode('final')->setTypeCompareOr()->filterClasses($interfaces)->mapSingleValue('name'));
            // dump($this->setSearchMode('final')->filterClasses($interfaces)->mapSingleValue('shortname'));
            // dump($this->getTargetFinalNames(WireWebpage::class, 'sections'));
            // foreach ($interfaces as $interface) {
            //     dump($this->getRepository($interface));
            // }
            // dd('STOP DEV TEST');
            if(!$this->allClassMetadatas->isValid()) {
                throw new InvalidArgumentException(vsprintf('Error %s line %d: class metadata collection is not valid! Please check your class metadata registration.', [__METHOD__, __LINE__]));
            }
        }
    }


    /************************************************************************************************************/
    /** INITIALIZE                                                                                              */
    /************************************************************************************************************/

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    protected function initialize(): void
    {
        if(!$this->initialized) {
            $this->allClassMetadatas = new WireClassMetadataCollection();
            // 1. Register managed entities
            foreach ($this->wireEm->em->getMetadataFactory()->getAllMetadata() as $classmetadata) {
                $this->internalRegisterWireClassMetadata(new WireClassMetadata($this, $classmetadata));
            }
            // 2. Register non-managed entities
            foreach ($this->allClassMetadatas as $classMetadata) {
                /** @var WireClassMetadataInterface $classMetadata */
                foreach ($classMetadata->getParentsNames() as $name => $shortname) {
                    if(!$this->allClassMetadatas->containsKey($name)) {
                        $this->internalRegisterWireClassMetadata(new WireClassMetadata($this, $name));
                    }
                }
            }
            $this->initialized = true;
        }
    }

    private function internalRegisterWireClassMetadata(WireClassMetadataInterface $wcmd): void
    {
        if($this->allClassMetadatas->contains($wcmd) || $this->allClassMetadatas->containsKey($wcmd->getName())) {
            if($this->isDev) {
                throw new InvalidArgumentException(vsprintf('Error %s line %d: %s for class %s is already registered', [__METHOD__, __LINE__, $wcmd::class, $wcmd->getName()]));
            }
        } else {
            $this->allClassMetadatas->add($wcmd);
        }
        if($this->isDev) {
            // $this->controlData();
        }
    }

    // public function registerWireClassMetadata(WireClassMetadataInterface $wcmd): void
    // {
    //     if($wcmd->isEntity()) {
    //         throw new InvalidArgumentException(vsprintf('Error %s line %d: cannot externally register a class metadata %s that is already an entity', [__METHOD__, __LINE__, $wcmd->getName()]));
    //     }
    //     $this->internalRegisterWireClassMetadata($wcmd);
    // }


    /************************************************************************************************************/
    /** RESET FILTERS                                                                                            */
    /************************************************************************************************************/

    public function resetFilters(): static
    {
        $this->resetSearchMode();
        $this->resetTypeCompare();
        return $this;
    }


    /************************************************************************************************************/
    /** SEARCH MODE                                                                                             */
    /************************************************************************************************************/

    public function setSearchMode(string $mode): static
    {
        if(!in_array($mode, static::SEARCH_MODES, true)) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: search mode %s is not valid! Valid modes are: %s', [__METHOD__, __LINE__, $mode, implode(', ', static::SEARCH_MODES)]));
        }
        $this->searchMode = $mode;
        return $this;
    }

    public function getSearchMode(): string
    {
        return $this->searchMode;
    }

    public function resetSearchMode(): static
    {
        $sm = static::SEARCH_MODES;
        $this->searchMode = reset($sm);
        return $this;
    }


    /************************************************************************************************************/
    /** TYPES MODE COMPARISON                                                                                   */
    /************************************************************************************************************/

    public function setTypeCompare(bool $typeCompare): static
    {
        $this->typeCompare = $typeCompare;
        return $this;
    }

    public function setTypeCompareAnd(): static
    {
        $this->typeCompare = true;
        return $this;
    }

    public function setTypeCompareOr(): static
    {
        $this->typeCompare = false;
        return $this;
    }

    public function getTypeCompare(): bool
    {
        return $this->typeCompare;
    }

    public function resetTypeCompare(): static
    {
        $this->typeCompare = true;
        return $this;
    }


    /************************************************************************************************************/
    /** MAP/FILTER RESULTS                                                                                      */
    /************************************************************************************************************/

    public function filterClasses(array $interfaces = [], ?WireClassMetadataCollectionInterface $results = null): WireClassMetadataCollectionInterface
    {
        $results ??= $this->allClassMetadatas;
        $filtered = $results->filter(
            function (WireClassMetadataInterface $wcmd) use ($interfaces) {
                // 1. Search
                switch ($this->searchMode) {
                    case 'all':
                        // All types of entities
                        break;
                    case 'final':
                        if(!$wcmd->isFinal()) return false;
                        break;
                    case 'managed':
                        if(!$wcmd->isManaged()) return false;
                        break;
                    case 'abstract':
                        if(!$wcmd->isAbstract()) return false;
                        break;
                    default:
                        # not recognized
                        throw new InvalidArgumentException(vsprintf('Error %s line %d: search mode "%s" is not recognized! Valid modes are: %s', [__METHOD__, __LINE__, $this->searchMode, implode(', ', static::SEARCH_MODES)]));
                        break;
                }
                // 2. Types
                if(!empty($interfaces)) {
                    if(!$wcmd->implementsInterfaces($interfaces, $this->typeCompare)) return false;
                }
                return true;
            }
        );
        $this->resetFilters();
        return $filtered;
    }


    /************************************************************************************************************/
    /** ENTITY MANAGER UTILITIES                                                                                */
    /************************************************************************************************************/

    public function getRepository(string $classname): ?EntityRepository
    {
        try {
            return $this->wireEm->em->getRepository($classname);
        } catch (Throwable $th) {
            $wcmd = $this->getWireClassMetadata($classname);
            if(!$wcmd->isManaged()) {
                $search = $this->setSearchMode('managed')->filterClasses([$classname])->mapSingleValue('name');
                if(count($search) !== 1) {
                    if($this->isDev) {
                        throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not managed by Doctrine! Found %d results for search: %s', [__METHOD__, __LINE__, $classname, count($search), json_encode($search)]));
                    }
                }
                return $this->getRepository(reset($search));
            }
        }
        if($this->isDev) {
            throw new Exception(vsprintf('Error %s line %d: class %s not found!%s- %s', [__METHOD__, __LINE__, $classname, PHP_EOL, $th->getMessage()]));
        }
        return null;
    }


    /************************************************************************************************************/
    /** MAIN INFOS                                                                                              */
    /************************************************************************************************************/

    public function getWireClassMetadatas(): WireClassMetadataCollectionInterface
    {
        return $this->allClassMetadatas;
    }

    public function getWireClassMetadata(string $classname): ?WireClassMetadataInterface
    {
        // Find by classname
        if($wireClassMetadata = $this->allClassMetadatas->get($classname)) {
            return $wireClassMetadata;
        }
        // Find by shortname
        foreach ($this->allClassMetadatas as $wireClassMetadata) {
            /** @var WireClassMetadataInterface $wireClassMetadata */
            if($wireClassMetadata->getShortname() === $classname) {
                return $wireClassMetadata;
            }
        }
        if($this->initialized) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: class metadata for class %s not found', [__METHOD__, __LINE__, $classname]));
        }
        return null;
    }


    /************************************************************************************************************/
    /** RELATIONS                                                                                               */
    /************************************************************************************************************/

    public function getTargetName(string $classname, string $relation): string
    {
        return $this->getWireClassMetadata($classname)->getTargetName($relation);
    }

    public function getTargetFinalNames(string $classname, string $relation): array
    {
        return $this->getWireClassMetadata($classname)->getTargetFinalNames($relation);
    }


    /************************************************************************************************************/
    /** DEV CONTROLS                                                                                            */
    /************************************************************************************************************/

    private function controlData(): void
    {
        if($this->isDev) {
            $this->wireEm->logger->debug(vsprintf('%s line %d: [DEV] control registered class metadata %s', [__METHOD__, __LINE__, static::class]));
            $shortnames = [];
            foreach ($this->allClassMetadatas as $classname => $wireClassMetadata) {
                /** @var WireClassMetadataInterface $wireClassMetadata */
                if(in_array($wireClassMetadata->getShortname(), $shortnames, true)) {
                    throw new InvalidArgumentException(vsprintf('Error %s line %d: class metadata for class %s has a duplicate shortname %s with other entit %s!', [__METHOD__, __LINE__, $classname, $wireClassMetadata->getShortname(), array_search($wireClassMetadata->getShortname(), $shortnames, true)]));
                }
                $shortnames[$wireClassMetadata->getClassname()] = $wireClassMetadata->getShortname();
            }
        }
    }

}