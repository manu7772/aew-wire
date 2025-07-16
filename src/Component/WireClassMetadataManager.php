<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Tools\Objects;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Attribute\DebugToOptimize;
use Aequation\WireBundle\Service\WireEntityManager;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitOwnerInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\BetweenManyInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WireLanguageInterface;
use Aequation\WireBundle\Entity\interface\TraitDatetimedInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Entity\interface\TraitWebpageableInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Service\interface\WireLanguageServiceInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataCollectionInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Psr\Log\LoggerInterface;
// PHP
use Exception;
use InvalidArgumentException;
use Symfony\Component\ObjectMapper\Attribute\Map;

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
        'instantiable', // Instantiable entities only (so managed entities that are not abstract and have no subclasses)
        'managed',      // Managed by EntityManager/Doctrine entities
        'abstract',     // Abstract entities only
    ];
    const STOPWATCH_NAME = 'WireClassMetadataManager::initialize';

    public readonly WireClassMetadataCollectionInterface $allClassMetadatas;
    public readonly EntityManagerInterface $em;
    protected string $searchMode;
    protected bool $typeCompare;
    public readonly bool $isDev;
    public readonly bool $isDevOrSadmin;
    public readonly PropertyAccessorInterface $accessor;
    private readonly Stopwatch $stopwatch;
    private bool $initialized = false;
    public readonly ValidatorInterface $validator;
    public readonly LoggerInterface $logger;

    public function __construct(
        public readonly WireEntityManager $wireEm,
    )
    {
        $this->isDev = $this->wireEm->isDev();
        $this->em = $this->wireEm->em;
        if($this->isDevOrSadmin = $this->wireEm->appWire->isDevOrSadmin()) {
            $this->stopwatch = new Stopwatch(true);
            $this->stopwatch->start(static::STOPWATCH_NAME);
        }
        $this->validator = $this->wireEm->validator;
        $this->logger = $this->wireEm->logger;
        $this->accessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidPropertyPath()->getPropertyAccessor();
        $this->resetFilters();
        $this->initialize();
        if($this->isDevOrSadmin) {
            $event = $this->stopwatch->stop(static::STOPWATCH_NAME);
            if($event->getDuration() >= 45) {
                // If the initialization took more than 40ms, we log a warning
                $message = vsprintf('%s line %d: [DEV] WireClassMetadataManager initialized in %d ms', [__METHOD__, __LINE__, $event->getDuration()]);
                $this->logger->warning($message);
                $this->wireEm->appWire->addFlash('warning', $message);
            }
            if(!$this->allClassMetadatas->isValid()) {
                throw new InvalidArgumentException(vsprintf('Error %s line %d: class metadata collection is not valid! Please check your class metadata registration.', [__METHOD__, __LINE__]));
            }
        }
    }


    /************************************************************************************************************/
    /** VARIABLES                                                                                               */
    /************************************************************************************************************/

    public static function getTypeChoices(): array
    {
        $types = array_keys(static::ENTITY_TYPES);
        return array_combine($types, $types);
    }

   public static function getModeChoices(): array
    {
        $modes = static::SEARCH_MODES;
        return array_combine($modes, $modes);
    }

    public function getClassnameChoices(): array
    {
        $choices = [];
        foreach ($this->allClassMetadatas as $wcmd) {
            /** @var WireClassMetadataInterface $wcmd */
            $choices[$wcmd->getShortname()] = $wcmd->name;
        }
        ksort($choices);
        return $choices;
    }

    public function getInterfaceChoices(): array
    {
        $choices = [];
        foreach ($this->allClassMetadatas as $wcmd) {
            /** @var WireClassMetadataInterface $wcmd */
            foreach ($wcmd->getInterfaces() as $rc) {
                $choices[$rc->getShortname()] ??= $rc->name;
            }
        }
        ksort($choices);
        return $choices;
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
            foreach ($this->em->getMetadataFactory()->getAllMetadata() as $classmetadata) {
                $this->internalRegisterWireClassMetadata(new WireClassMetadata($this, $classmetadata));
            }
            // 2. Register non-managed entities
            foreach ($this->allClassMetadatas as $classMetadata) {
                if(count($classMetadata->getSubclasses())) {
                    continue;
                }
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


    /************************************************************************************************************/
    /** FIND ENTITY CLASS                                                                                       */
    /************************************************************************************************************/

    /**
     * Find the classname of an entity defined by its classname, shortname or object.
     * 
     * @param string|object $entity
     * @return string|null
     */
    public function findEntityClassname(string|object $entity): ?string
    {
        $classname = is_object($entity) ? Objects::getClassname($entity) : $entity;
        $classnames = $this->filterClasses()->mapSingleValue('shortname');
        $found = array_key_exists($classname, $classnames) ? $classname : array_search($classname, $classnames, true);
        return $found ?: null;
    }

    /**
     * Transform shortnames or objects to classnames in list of classnames/interfaces
     * 
     * @param string|array|object $list
     * @return null|string|array
     */
    public function transformToClassnames(string|array|object $list): null|string|array
    {
        if(!is_array($list)) {
            return $this->findEntityClassname($list);
        }
        $classnames = [];
        foreach ($list as $item) {
            switch (true) {
                case is_string($item) && (class_exists($item) || interface_exists($item)):
                    $classnames[] = $item;
                    break;
                case is_array($item):
                    foreach ($item as $val) {
                        $classnames[] = $this->findEntityClassname($val) ?: $val;
                    }
                    break;
                case is_object($item) || is_string($item):
                    $classnames[] = $this->findEntityClassname($item) ?: $item;
                    break;
                default:
                    throw new InvalidArgumentException(vsprintf('Error %s line %d: item %s is not a valid data!', [__METHOD__, __LINE__, Objects::toDebugString($item)]));
                    break;
            }
        }
        return array_unique($classnames);
    }


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

    /**
     * Sets the search mode for filtering class metadata.
     * This method allows you to specify how the class metadata should be filtered based on the search mode.
     * 
     * @param string $mode The search mode to set. Valid modes are: 'all', 'appwire', between', 'translation', 'hydratable'.
     * @return static
     */
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

    /**
     * Sets the type comparison mode.
     * 
     * If true, the comparison will be a AND comparison
     * If false, the comparison will be an OR comparison.
     * 
     * @param bool $typeCompare
     * @return static
     */
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

    public function getAll(): WireClassMetadataCollectionInterface
    {
        return $this->allClassMetadatas;
    }

    public function filterClasses(array $interfaces = [], ?WireClassMetadataCollectionInterface $results = null): WireClassMetadataCollectionInterface
    {
        $interfaces = $this->transformToClassnames($interfaces);
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
                    case 'instantiable':
                        if(!$wcmd->isInstantiable()) return false;
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
    /** SEARCH UTILITIES                                                                                        */
    /************************************************************************************************************/

    /**
     * Find classes that implement the given interfaces and mode (available modes: 'final', 'instantiable', 'managed', 'abstract').
     * 
     * @param string $mode
     * @param array $interfaces
     * @return WireClassMetadataCollectionInterface
     */
    public function findByType(string $mode = 'all', array $interfaces = []): WireClassMetadataCollectionInterface
    {
        return $this->setSearchMode($mode)->filterClasses($interfaces);
    }

    /**
     * Find all classes that implement the given interfaces with mode "instantiable".
     * 
     * @param array $interfaces
     * @return WireClassMetadataCollectionInterface
     */
    public function findInstantiables(array $interfaces = []): WireClassMetadataCollectionInterface
    {
        return $this->findByType('instantiable', $interfaces);
    }

    /**
     * Find all classes that implement the given interfaces with mode "managed".
     * 
     * @param array $interfaces
     * @return WireClassMetadataCollectionInterface
     */
    public function findManageds(array $interfaces = []): WireClassMetadataCollectionInterface
    {
        return $this->findByType('managed', $interfaces);
    }

    /**
     * Find all classes that implement the given interfaces with mode "final".
     * 
     * @param array $interfaces
     * @return WireClassMetadataCollectionInterface
     */
    public function findFinals(array $interfaces = []): WireClassMetadataCollectionInterface
    {
        return $this->findByType('final', $interfaces);
    }

    /**
     * Find one class that implements the given interfaces and mode (available modes: 'final', 'instantiable', 'managed', 'abstract').
     * 
     * @param string $mode
     * @param array $interfaces
     * @return WireClassMetadataInterface
     */
    public function findOneByType(string $mode = 'all', array $interfaces): WireClassMetadataInterface
    {
        $finals = $this->findByType($mode, $interfaces);
        if($finals->count() === 1) {
            return $finals->first();
        }
        throw new InvalidArgumentException(vsprintf('Error %s line %d: found more or less than one "%s" (exactly %d) classes for search %s: %s', [__METHOD__, __LINE__, $mode, $finals->count(), json_encode($interfaces), implode(', ', $finals->getKeys())]));
    }

    public function findOneOrNullByType(string $mode = 'all', array $interfaces): ?WireClassMetadataInterface
    {
        $finals = $this->findByType($mode, $interfaces);
        return $finals->count() === 1 ? $finals->first() : null;
    }

    /**
     * Find one instantiable class that implements the given interfaces.
     * 
     * @param array $interfaces
     * @return WireClassMetadataInterface
     */
    public function findOneInstantiable(array $interfaces): WireClassMetadataInterface
    {
        return $this->findOneByType('instantiable', $interfaces);
    }

    public function findOneOrNullInstantiable(array $interfaces): ?WireClassMetadataInterface
    {
        return $this->findOneOrNullByType('instantiable', $interfaces);
    }

    /**
     * Find one managed class that implements the given interfaces.
     * 
     * @param array $interfaces
     * @return WireClassMetadataInterface
     */
    public function findOneManaged(array $interfaces): WireClassMetadataInterface
    {
        return $this->findOneByType('managed', $interfaces);
    }

    public function findOneOrNullManaged(array $interfaces): ?WireClassMetadataInterface
    {
        return $this->findOneOrNullByType('managed', $interfaces);
    }

    /**
     * Find one final class that implements the given interfaces.
     * 
     * @param array $interfaces
     * @return WireClassMetadataInterface
     */
    public function findOneFinal(array $interfaces): WireClassMetadataInterface
    {
        return $this->findOneByType('final', $interfaces);
    }

    public function findOneOrNullFinal(array $interfaces): ?WireClassMetadataInterface
    {
        return $this->findOneOrNullByType('final', $interfaces);
    }


    /************************************************************************************************************/
    /** ENTITY MANAGER & UTILITIES                                                                              */
    /************************************************************************************************************/

    public function getService(string|object $classname): ?WireEntityServiceInterface
    {
        $wCmd = $this->getWireClassMetadata($classname);
        $service = $wCmd?->getService() ?? null;
        if($service && $this->isDev) {
            if(!($service instanceof WireEntityServiceInterface)) {
                throw new InvalidArgumentException(vsprintf('Error %s line %d: service %s is not an instance of %s for entity of class %s!', [__METHOD__, __LINE__, $service::class, WireEntityServiceInterface::class, $wCmd->name]));
            }
            if(!is_a($wCmd->name, $service->getEntityClassname(), true)) {
                throw new InvalidArgumentException(vsprintf('Error %s line %d: service %s is not available for entity of class %s!', [__METHOD__, __LINE__, $service::class, WireEntityServiceInterface::class]));
            }
        }
        return $service instanceof WireEntityServiceInterface ? $service : null;
    }

    public function getRepository(string $classname): EntityRepository
    {
        $wCmd = $this->getWireClassMetadata($classname);
        return $wCmd->getRepository($classname);
    }

    public function getDtoSourceMaps(string|object $classname): array
    {
        $wCmd = $this->findOneFinal([Objects::getClassname($classname)]);
        if(!$wCmd) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not registered in the metadata manager!', [__METHOD__, __LINE__, $classname]));
        }
        return $wCmd->getDtoSourceMaps();
    }

    public function getFirstDtoSourceMap(string|object $classname): ?Map
    {
        $wCmd = $this->findOneFinal([Objects::getClassname($classname)]);
        if(!$wCmd) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not registered in the metadata manager!', [__METHOD__, __LINE__, $classname]));
        }
        return $wCmd->getFirstDtoSourceMap();
    }

    public function getDtoTargetMaps(string|object $classname): array
    {
        $wCmd = $this->findOneFinal([Objects::getClassname($classname)]);
        if(!$wCmd) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not registered in the metadata manager!', [__METHOD__, __LINE__, $classname]));
        }
        return $wCmd->getDtoTargetMaps();
    }

    public function getFirstDtoTargetMap(string|object $classname): ?Map
    {
        $wCmd = $this->findOneFinal([Objects::getClassname($classname)]);
        if(!$wCmd) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not registered in the metadata manager!', [__METHOD__, __LINE__, $classname]));
        }
        return $wCmd->getFirstDtoTargetMap();
    }



    /************************************************************************************************************/
    /** NEW INSTANCE                                                                                            */
    /************************************************************************************************************/

    /**
     * Create a new instance of entity
     * 
     * @param string $classname
     * @param mixed $data
     * @return object
     * @throws InvalidArgumentException If the class cannot be instantiated
     */
    public function newInstance(string $classname, mixed $data = null, array $context = []): object
    {
        if($wCmd = $this->getWireClassMetadata($classname)) {
            if(!$wCmd->isInstantiable()) {
                $wCmd = $this->findOneInstantiable([$wCmd->name]);
            }
            $entity = $wCmd->newInstance($data);
            $this->postCreated($entity, $context);
            return $entity;
        }
        throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not available to create a new instance.', [__METHOD__, __LINE__, $classname]));
    }

    /**
     * Create a new model of entity (not for persist use)
     * 
     * @param string $classname
     * @param mixed $data
     * @return WireEntityInterface
     * @throws InvalidArgumentException If the class cannot be instantiated
     */
    public function newModel(string $classname, mixed $data = null, array $context = []): object
    {
        if($wCmd = $this->getWireClassMetadata($classname)) {
            if(!$wCmd->isInstantiable()) {
                $wCmd = $this->findOneInstantiable([$wCmd->name]);
            }
            $entity = $wCmd->newModel($data);
            $this->postCreated($entity, $context);
            return $entity;
        }
        throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not available to create a new model instance.', [__METHOD__, __LINE__, $classname]));
    }

    // public function newDto(string $classname, mixed $data, array $context = []): WireEntityDtoInterface
    // {
    //     if($wCmd = $this->getWireClassMetadata($classname)) {
    //         if(!$wCmd->isInstantiable()) {
    //             $wCmd = $this->findOneInstantiable([$wCmd->name]);
    //         }
    //         $entity = $wCmd->newDto($data, $context);
    //         return $entity;
    //     }
    //     throw new InvalidArgumentException(vsprintf('Error %s line %d: class %s is not available to create a new DTO instance.', [__METHOD__, __LINE__, $classname]));
    // }


    /************************************************************************************************************/
    /** EVENTS                                                                                                  */
    /************************************************************************************************************/

    /**
     * After a new entity created
     * 
     * @param object $entity
     * @return void
     */
    public function postCreated(object $entity): void
    {
        if($entity instanceof BaseEntityInterface) {
            if(!$entity->getSelfState()->isExactState('new')) {
                if(!$entity->getSelfState()->isModel()) {
                    // dump($entity->getSelfState()->getReport());
                    throw new Exception(vsprintf('Error %s line %d: entity %s is not new ONLY (and not a MODEL either)!', [__METHOD__, __LINE__, $entity->getClassname()]));
                }
            }
            if($entity->getSelfState()->isPostCreated() ?? false) {
                // Entity created events already done
                $message = vsprintf('%s line %d: %s (id: %s) already %s!', [__METHOD__, __LINE__, $entity->getClassname(), $entity->getId() ?? 'NULL', __FUNCTION__]);
                if($this->isDev) {
                    throw new Exception('Error '.$message);
                }
                $this->logger->warning('Debug '.$message);
                return;
            }
            // Prepare Embedded status + start it
            $entity->getSelfState()->initiateEmbed($this->wireEm->appWire, true);
            // First apply internal events
            $entity->getSelfState()->applyEvents();
            // Then apply external events
            $this->defaultEventActions($entity, __FUNCTION__);
        }
    }

    /**
     * After a entity is loaded from database
     * 
     * @param object $entity
     * @return void
     */
    public function postLoaded(object $entity): void
    {
        if($entity instanceof BaseEntityInterface) {
            $entity->initializeSelfstate();
            if(!$entity->getSelfState()->isExactState('loaded')) {
                if($entity->getSelfState()->isModel()) {
                    throw new Exception(vsprintf('Error %s line %d: entity %s is a model, not an entity!', [__METHOD__, __LINE__, $entity->getClassname()]));
                }
                throw new Exception(vsprintf('Error %s line %d: entity %s is not loaded ONLY!', [__METHOD__, __LINE__, $entity->getClassname()]));
            }
            if($entity->getSelfState()->isPostLoaded() ?? false) {
                // Entity loaded events already done
                $message = vsprintf('%s line %d: %s (id: %s) already %s!', [__METHOD__, __LINE__, $entity->getClassname(), $entity->getId() ?? 'NULL', __FUNCTION__]);
                if($this->isDev) {
                    throw new Exception('Error '.$message);
                }
                $this->logger->warning('Debug '.$message);
                return;
            }
            // Prepare Embedded status
            $entity->getSelfState()->initiateEmbed($this->wireEm->appWire, false);
            // First apply internal events
            $entity->getSelfState()->applyEvents();
            // Then apply external events
            // ...
            $this->defaultEventActions($entity, __FUNCTION__);
        }
    }

    #[DebugToOptimize('warning', 'TraitWebpageableInterface : Créer un champ JSON dans WireWebpageInterface pour définir les types (liste d\'interfaces) d\'entités à afficher + (bool) pour chaque type')]
    protected function defaultEventActions(
        object $entity,
        string $eventName,
    ): void
    {
        if(!in_array($eventName, $valid_events = ['postCreated', 'postLoaded'])) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: event name %s is not recognized!%s- Valid events are: %s', [__METHOD__, __LINE__, $eventName, PHP_EOL, implode(', ', $valid_events)]));
        }
        if($entity->getSelfState()->isModel()) return;
        $actions = [
            TraitOwnerInterface::class => [
                // 'postLoaded',
                'postCreated',
            ],
            TraitWebpageableInterface::class => [
                // 'postLoaded',
                'postCreated',
            ],
            TraitUnamedInterface::class => [
                // 'postLoaded',
                'postCreated',
            ],
            TraitDatetimedInterface::class => [
                // 'postLoaded',
                'postCreated',
            ],
        ];
        foreach ($actions as $interface => $triggers) {
            if(in_array($eventName, $triggers) && is_a($entity, $interface)) {
                switch ($interface) {
                    case TraitOwnerInterface::class:
                        /** @var TraitOwnerInterface $entity */
                        if(empty($entity->getOwner())) {
                            $user = $this->wireEm->appWire->getUser();
                            if ($user) {
                                $entity->setOwner($user);
                            } else if ($entity->isOwnerRequired()) {
                                $userService = $this->wireEm->appWire->get(WireUserServiceInterface::class);
                                $admin = $userService->getMainAdmin();
                                if ($admin) {
                                    $entity->setOwner($admin);
                                } else if ($this->isDev) {
                                    throw new Exception(vsprintf('Error %s line %d: entity %s %s has no owner!', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString()]));
                                }
                            }
                        }
                        break;
                    case TraitWebpageableInterface::class:
                        /** @var TraitWebpageableInterface $entity */
                        if(empty($entity->getWebpage())) {
                            $unames = [
                                WireUserInterface::class => 'wp_user_presentation',
                            ];
                            foreach ($unames as $if => $uname) {
                                if(is_a($entity, $if) && $webpage = $this->wireEm->findByUname($uname)) {
                                    $entity->setWebpage($webpage);
                                    break;
                                }
                            }
                        }
                        break;
                    case TraitDatetimedInterface::class:
                        /** @var TraitDatetimedInterface $entity */
                        if(empty($entity->getLanguage())) {
                            if($defaultLanguage = $this->wireEm->appWire->getCurrentLanguage()) {
                                $entity->setLanguage($defaultLanguage);
                                // Default timezone setted automatically
                            } else {
                                /** @var WireLanguageServiceInterface */
                                $service = $this->getService(WireLanguageInterface::class);
                                if($prefered = $service->getPreferedLanguage()) {
                                    $entity->setLanguage($prefered);
                                }
                            }
                        }
                        break;
                    case TraitUnamedInterface::class:
                        /** @var TraitUnamedInterface $entity */
                        if(empty($entity->getUname())) {
                            throw new Exception(vsprintf('Error %s line %d: entity %s %s has no Uname! Please set it before calling %s.', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString(), $eventName]));
                        }
                        if(!$entity->getUname()->getSelfState()->isReady()) {
                            throw new Exception(vsprintf('Error %s line %d: entity %s %s has Uname (%s) selfstate not ready! Please set it before calling %s.', [__METHOD__, __LINE__, $entity->getClassname(), $entity->__toString(), $entity->getUname(), $eventName]));
                        }
                        break;
                    default:
                        // Action not recognized
                        throw new InvalidArgumentException(vsprintf('Error %s line %d: action for interface %s not recognized!', [__METHOD__, __LINE__, $interface]));
                        break;
                }
            }
        }
    }


    /************************************************************************************************************/
    /** VALIDATION                                                                                              */
    /************************************************************************************************************/

    public function validateEntity(object $entity, string|GroupSequence|array|null $addGroups = null, Constraint|array|null $constraints = null): ConstraintViolationListInterface
    {
        $groups = [];
        if($entity instanceof BaseEntityInterface) {
            $groups = $entity->__selfstate->isNew() ? ['persist'] : ['update'];
        }
        return $this->validator->validate($entity, $constraints, array_unique(array_merge($groups, $addGroups ?? [])));
    }


    /************************************************************************************************************/
    /** MAIN INFOS                                                                                              */
    /************************************************************************************************************/

    public function getWireClassMetadatas(): WireClassMetadataCollectionInterface
    {
        return $this->allClassMetadatas;
    }

    public function getWireClassMetadata(string|object $classname): ?WireClassMetadataInterface
    {
        if(is_object($classname)) {
            $classname = Objects::getClassname($classname);
        }
        // Find by classname
        if($wireClassMetadata = $this->allClassMetadatas->get($classname)) {
            return $wireClassMetadata;
        }
        if(interface_exists($classname)) {
            // Find by interface
            return $this->findOneFinal([$classname]);
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
            $this->logger->debug(vsprintf('%s line %d: [DEV] control registered class metadata %s', [__METHOD__, __LINE__, static::class]));
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