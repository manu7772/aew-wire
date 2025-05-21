<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\EntityContainerInterface;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\RelationMapperInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Entity\WireLanguage;
use Aequation\WireBundle\Entity\WirePhonelink;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\NormalizerService;
use Aequation\WireBundle\Tools\Objects;
use Doctrine\Common\Collections\ArrayCollection;
// Symfony
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
// PHP
use Twig\Markup;
use Exception;
use InvalidArgumentException;
use Throwable;

/**
 * Entity Container for denormalization
 * ====================================
 * 
 * - mode CREATE ONLY: only create new entities - do not update existing ones
 * 
 * 
 * 
 * 
 */
class EntityContainer implements EntityContainerInterface
{
    public const KEEP_INDEXES = false;
    public const UNDEFINED_REPORT_VALUE = '--- undefined ---';

    protected readonly NormalizerServiceInterface $normalizer;
    protected readonly BaseEntityInterface $entity;
    protected RelationMapperInterface $relationMapper;
    public readonly WireEntityManagerInterface $wireEm;
    public readonly ?EntityContainerInterface $parent;
    public readonly int $level;
    protected array $rawdata = [];
    protected array $extradata = [];
    protected bool $compiled;
    // Controls
    protected Opresult $controls;
    protected bool $settingControl = false;
    protected bool $compiledControl = false;

    public function __construct(
        NormalizerServiceInterface|EntityContainerInterface $starter,
        public readonly string $classname,
        public array $data,
        public array $context = [],
        public readonly ?string $parentProperty = null,
    ) {
        $this->controls = new Opresult();
        $this->compiled = false;
        // 1 - Start Container
        if($starter instanceof NormalizerServiceInterface) {
            // First initialization - is root container
            $this->parent = null;
            $this->normalizer = $starter;
            $this->relationMapper = $this->normalizer->getRelationMapper($this->classname);
            $this->wireEm = $starter->wireEm;
            $this->level = 0;
            $this->mergeContext($context, false);
            if($this->parent?->isModel() ?? false) {
                $this->mergeContext([static::CONTEXT_AS_MODEL => true], true);
            }
            if(!empty($this->parentProperty)) {
                $message = vsprintf('Error %s line %d: Parent property should be empty for root container. Got %s', [__METHOD__, __LINE__, $this->parentProperty]);
                $this->addError($message, static::THROW_EXCEPTION_ON_ERROR_NOW);
            }
        } else {
            // Child container
            $this->parent = $starter;
            $this->normalizer = $this->parent->normalizer;
            $this->relationMapper = $this->normalizer->getRelationMapper($this->classname);
            $this->wireEm = $this->parent->wireEm;
            $this->level = $this->parent->getLevel() + 1;
            $this->mergeContext($context, false);
            if($this->parent?->isModel() ?? false) {
                $this->mergeContext([static::CONTEXT_AS_MODEL => true], true);
            }
            if(empty($this->parentProperty)) {
                $message = vsprintf('Error %s line %d: Parent property should not be empty for child container of parent entity %s.', [__METHOD__, __LINE__, $this->parent->getClassname()]);
                $this->addError($message, static::THROW_EXCEPTION_ON_ERROR_NOW);
            }
            if(empty($this->parent->getEntity())) {
                $message = vsprintf('Error %s line %d: Parent entity should not be empty for parent entity %s of child container %s (property: "%s").', [__METHOD__, __LINE__, $this->parent->getClassname(), $this->classname, $this->parentProperty]);
                $this->addError($message, static::THROW_EXCEPTION_ON_ERROR_NOW);
            }
        }
        // --- Controls
        if(!$this->wireEm->entityExists($this->classname, false, true)) {
            $message = vsprintf('Error %s line %d: Entity %s does not exist!', [__METHOD__, __LINE__, $this->classname]);
            $this->addError($message, false);
        }
        if(!$this->isValid()) return;
        if(!$this->relationMapper->isValid()) {
            $message = vsprintf('Error %s line %d: RelationMapper is not valid!%sErrors:%s', [__METHOD__, __LINE__, PHP_EOL, PHP_EOL.$this->relationMapper->getMessagesAsString(false, false)]);
            $this->addError($message, false);
        }
        if(empty($this->data)) {
            $message = vsprintf('Error %s line %d: Data is empty', [__METHOD__, __LINE__]);
            $this->addError($message, static::THROW_EXCEPTION_ON_ERROR_NOW);
        }
        $this->throwErrors();
        // 3 - Raw data
        $this->compileRawData();
        if(!$this->isValid()) return;
        // 4 - Entity
        // $this->tryFindEntity();
        // if(!$this->isValid()) return;
        // 5 - Settings control
        $this->globalControl();
        // if($this->isValid()) dump($this->getInfo());
        // $this->compileFinalData();
    }

    public function __toString(): string
    {
        return Objects::getShortname($this).'@'.$this->classname;
    }

    public function compile(): static
    {
        $this->compileFinalData();
        return $this;
    }

    public function isValid(): bool
    {
        return !$this->controls->hasFail();
    }

    private function addError(string $message, $trigger_exception = false): void
    {
        $this->controls->addError($message);
        if($trigger_exception || static::TRIGGER_EXCEPTION_ON_ERROR) {
            // dump($this->getInfo([], $message));
            throw new Exception($message);
        }
    }

    private function throwErrors(): void
    {
        if($this->controls->hasFail()) {
            $message = vsprintf('Errors %s line %d:%s%s', [__METHOD__, __LINE__, PHP_EOL, $this->getMessagesAsString(false, false)]);
            throw new Exception($message);
        }
    }

    private function addWarning(string $message): void
    {
        $this->controls->addWarning($message);
    }

    public function getControls(): OpresultInterface
    {
        return $this->controls;
    }

    public function getErrorMessages(): array
    {
        return $this->controls->getMessages('danger');
    }

    public function getMessagesAsString(?bool $asHtml = null, bool $byTypes = true, null|string|array $msgtypes = null): string|Markup
    {
        return $this->controls->getMessagesAsString($asHtml, $byTypes, $msgtypes);
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function isMaxLevel(): bool
    {
        $max = $this->isModel() ? static::MODELS_ASSOCIATIONS_MAX_LEVEL : static::STD_ASSOCIATIONS_MAX_LEVEL;
        return $this->level >= $max;
    }

    public function isRoot(): bool
    {
        return empty($this->parent);
    }

    public function getParent(): ?EntityContainerInterface
    {
        return $this->parent ?? null;
    }

    public function isRelationWithParent(): bool
    {
        return $this->isRoot()
            ? false
            : $this->parent->getRelationMapper()->hasRelation($this->parentProperty);
    }
    
    public function getClassname(): string
    {
        return $this->classname;
    }

    public function getClassnames(): array
    {
        $parent = $this;
        $classnames = [$parent->classname];
        while ($parent = $parent->getParent()) {
            $classnames[] = $parent->getClassname();
        }
        return $classnames;
    }

    public function getShortname(): string
    {
        return Objects::getShortname($this->classname);
    }

    public function getCompiledData(): array
    {
        $this->compileFinalData();
        return $this->data;
    }

    public function getRawdata(
        bool $withExtradata = false,
    ): array
    {
        $this->compileRawData();
        return $withExtradata
            ? array_merge($this->rawdata, [static::EXTRA_DATA_NAME => $this->extradata])
            : $this->rawdata;
    }

    public function isLoaded(): bool
    {
        return isset($this->entity)
            ? $this->entity->getSelfState()->isLoaded()
            : false;
    }

    public function isCreated(): bool
    {
        return isset($this->entity)
            ? $this->entity->getSelfState()->isNew()
            : false;
    }

    public function getRelationMapper(): RelationMapperInterface
    {
        return $this->relationMapper;
    }


    /***********************************************************************************************
     * ENTITY
     **********************************************************************************************/

    private function tryFindEntity(): bool
    {
        if(!$this->hasEntity()) {
            $this->wireEm->incDebugMode();
            $entity = null;
            // 1. Try find entity by euid or uname in database
            if(isset($this->rawdata['euid'])) {
                $entity = $this->normalizer->findEntityByEuid($this->rawdata['euid']);
            }
            if(!$entity && is_string($this->rawdata['uname']['uname'] ?? null)) {
                $entity = $this->normalizer->findEntityByUname($this->rawdata['uname']['uname']);
            }
            if(!$entity && $this->classname === Uname::class && is_string($this->rawdata['uname'] ?? null)) {
                /** @var TraitUnamedInterface */
                $parent = $this->parent->getEntity();
                if($uname = $parent->getUname()) {
                    $entity = $uname;
                }
                $entity ??= $this->normalizer->findUnameByUname($this->rawdata['uname']);
                // dump($entity, 'Update allowed for '.$this->getShortname().': '.json_encode($this->isUpdateAllowed())/*, $this->parent->getEntity(), $this->parent->getClassname(), $this->parent->getEntity()->getSelfState()*/);
            }
            // Controls
            // if($entity && $entity->getSelfState()->isLoaded() && !$this->isUpdateAllowed()) {
            //     $this->setEntity($entity);
            //     $info = [
            //         'is_update_allowed' => $this->isUpdateAllowed(),
            //         'hierarchy_classnames' => ($this->parent ? '[parent] '.$this->parent->getClassname().' => [child] ' : '').$this->classname,
            //         'parent_info' => $this->parent ? $this->parent->getInfo() : null,
            //         'parent_entity_report' => $this->parent ? $this->parent->getEntity()->getSelfState()->getReport() : null,
            //         'child_info' => $this->getInfo(),
            //         'child_entity_report' => $entity->getSelfState()->getReport(),
            //     ];
            //     dump($info);
            //     throw new Exception(vsprintf('Error %s line %d: Entity %s is already loaded but not allowed to update!', [__METHOD__, __LINE__, $entity::class]));
            // }
            if(!$entity && $this->classname === Uname::class && $this->parent->getEntity()->getSelfState()->isLoaded()) {
                // Uname entity
                // dump($this->parent->getEntity()->getSelfState()->getReport(), $this->getInfo());
                throw new Exception(vsprintf('Error %s line %d:%s- %s is new but parent entity %s is loaded!', [__METHOD__, __LINE__, PHP_EOL, $this->getShortname(), $this->parent->getShortname()]));
            }
            // 2. Not found, create new entity if allowed
            if(!$entity && $this->isCreateAllowed()) {
                $entity = $this->wireEm->createEntity($this->classname, false);
            }
            // if(is_a($this->classname, WireWebpageInterface::class, true)) {
            //     // Webpage entity
            //     dump($this->normalizer->getCreateds()->toArray());
            //     dd($this->getInfo(), $this->rawdata, ($entity ?? null) ? $entity : null, ($entity ?? null) ? $entity->getSelfState()->getReport() : null, ($this->parent ?? null) ? $this->parent->getEntity() : null);
            // }
            if($entity) {
                $this->setEntity($entity);
            } else {
                $this->getInfo();
            }
            $this->wireEm->decDebugMode();
        }
        return $this->hasEntity();
    }
 
    public function setEntity(BaseEntityInterface $entity): void
    {
        if(!$this->hasEntity()) {
            // if(is_a($entity->getClassname(), WirePhonelink::class, true)) {
            //     // Phonelink entity
            //     dd($this->getInfo(), $entity->getSelfState()->getReport());
            // }
            // Check validity
            if(!is_a($entity->getClassname(), $this->classname, true)) {
                // Invalid classname
                $message = vsprintf('Error %s line %d: Entity classname %s does not match container classname %s', [__METHOD__, __LINE__, $entity::class, $this->classname]);
                $this->addError($message, false);
                return;
            }
            if($this->isModel()) {
                $entity->getSelfState()->setModel();
            }
            $this->entity = $entity;
            // Set Uname first
            if($this->entity instanceof TraitUnamedInterface && isset($this->rawdata['uname']['uname'])) {
                $this->entity->setUname($this->rawdata['uname']['uname']);
            }
            $this->normalizer->addCreated($this->entity);
            // Controls
            // if($this->isModel()) {
            //     if($this->parent && !$this->parent->isModel()) {
            //         $this->addWarning(vsprintf('Error %s line %d: %s is MODEL but parent is NOT MODEL!', [__METHOD__, __LINE__, $this->entity::class]));
            //     }
            //     // $entity->getSelfState()->setModel(); // --> already set just before
            //     if($this->entity->getSelfState()->isContained()) {
            //         $this->addWarning(vsprintf('Error %s line %d: Entity %s is MODEL but is already managed by the entity manager! So it will be detached.', [__METHOD__, __LINE__, $this->entity::class]));
            //         $this->wireEm->getEm()->detach($this->entity);
            //     }
            // }
            // if(!$this->isRoot()) {
            //     // dump(vsprintf('Info %s: Entity %s, child of %s (%s) by property "%s" is: %s', [Objects::getShortname(__METHOD__), $entity->getShortname(), Objects::getShortname($this->parent->getClassname()), $this->parent->getEntity()/* .'::'.($this->parent->getEntity()->getSelfState()->isNew() ? 'NEW' : 'LOADED') */, $this->parentProperty, $entity->getSelfState()->isNew() ? 'NEW' : 'LOADED']));
            //     // Check by root relation type
            //     if(!$this->isCreateAllowed() && $entity->getSelfState()->isNew()) {
            //         // Create only by relation
            //         $message = vsprintf('Error %s line %d: Entity %s, child of %s by property %s should be created!', [__METHOD__, __LINE__, $entity::class, $this->parent->getClassname(), $this->parentProperty]);
            //         $this->addError($message, static::THROW_EXCEPTION_ON_ERROR_NOW);
            //         return;
            //     }
            //     if($entity->getSelfState()->isNew() && $this->parent->getEntity()->getSelfState()->isLoaded()) {
            //         // Create only by relation
            //         dump($entity->getSelfState()->getReport(), $this->parent->getEntity()->getSelfState()->getReport());
            //         $message = vsprintf('Error %s line %d: %s, child of %s by property %s should not be created!', [__METHOD__, __LINE__, $entity::class, $this->parent->getClassname(), $this->parentProperty]);
            //         $this->addError($message, static::THROW_EXCEPTION_ON_ERROR_NOW);
            //         return;
            //     }
            // }
            // if($this->isCreated() && !$this->isModel()) {
            //     $this->normalizer->addCreated($this->entity);
            // }
        } else {
            // // Check if entity is already set
            // if($this->entity !== $entity) {
            //     // Entity is already set but not the same
            //     $message = vsprintf('Error %s line %d: Entity %s is already defined and not the same!', [__METHOD__, __LINE__, $this->entity::class]);
            //     dump($this->entity, $entity);
            //     $this->addError($message, static::THROW_EXCEPTION_ON_ERROR_NOW);
            //     return;
            // }
        }
    }

    public function getEntity(): ?BaseEntityInterface
    {
        return $this->entity ?? null;
    }

    public function hasEntity(): bool
    {
        return isset($this->entity);
    }

    public function getEntityDenormalized(?string $format = null, array $context = []): ?BaseEntityInterface
    {
        $this->compileFinalData();
        if(!$this->hasEntity()) {
            return null;
        }
        $this->mergeContext($context, false);
        $context = $this->getDenormalizationContext();
        if(!isset($context[AbstractNormalizer::OBJECT_TO_POPULATE]) || ($context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? null) !== $this->entity) {
            dump($this->entity->getSelfState()->getReport(), isset($context[AbstractNormalizer::OBJECT_TO_POPULATE]) ? $context[AbstractNormalizer::OBJECT_TO_POPULATE]->getSelfState()->getReport() : 'null');
            throw new InvalidArgumentException(vsprintf('Error %s line %d: OBJECT_TO_POPULATE should be the same as entity!', [__METHOD__, __LINE__]));
        }
        if($this->entity->getSelfState()->isNew() && !$this->entity->getSelfState()->isModel() && !$this->willPersist()) {
            // New entity and not allowed to persist
            $this->wireEm->logger->warning(vsprintf('Error %s line %d: Entity %s is new and not allowed to persist!', [__METHOD__, __LINE__, $this->entity::class]));
            return null;
        }
        if($this->entity->getSelfState()->isLoaded() && !$this->willUpdate()) {
            // Already loaded and not allowed to update
            // return $this->getEntity();
        } else {
            $this->normalizer->getSerializer()->denormalize(
                $this->getCompiledData(),
                $this->getClassname(),
                $format,
                $context
            );
            $this->finalizeEntity();
            // return $this->getEntity();
            // dump($this->getEntity());
        }
        // if(!$this->isModel()) {
            // $this->normalizer->addCreated($this->getEntity());
        // }
        return $this->getEntity();
    }

    private function finalizeEntity(): void
    {
        if(!empty($this->extradata)) {
            // Apply extra data
            // $this->normalizer->logger->warning(vsprintf('Applying extra data to entity %s: %s', [$this->classname, json_encode($this->extradata)]));
            foreach ($this->extradata as $action => $values) {
                switch ($action) {
                    case 'reverse':
                        $this->normalizer->logger->info(vsprintf('Applying reverse extra data to entity %s: %s', [$this->classname, json_encode($values)]));
                        foreach ($values as $uname => $property) {
                            if($target = $this->normalizer->findEntityByUname($uname)) {
                                Objects::setPropertyValue($target, $property, $this->entity);
                            } else if($this->wireEm->isDev()) {
                                throw new Exception(vsprintf('Error %s line %d: Entity unamed %s not found!', [__METHOD__, __LINE__, $uname]));
                            }
                        }
                        break;
                    default:
                        $this->normalizer->logger->warning(vsprintf('Error %s line %d: Unknown action %s in extra data!', [__METHOD__, __LINE__, $action]));
                        break;
                }
            }
        }
    }


    /***********************************************************************************************
     * CONTEXT
     **********************************************************************************************/

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): static
    {
        $this->context = $context;
        if($this->isRoot()) {
            // main group
            $this->setMainGroup($this->context[static::CONTEXT_MAIN_GROUP] ?? null);
            // is model
            $this->context[static::CONTEXT_AS_MODEL] ??= false;
            // create only
            $this->context[static::CONTEXT_DO_NOT_UPDATE] ??= false;
            $this->context[static::CONTEXT_DO_NOT_CREATE] ??= false;
        } else {
            // main group
            $this->setMainGroup($this->context[static::CONTEXT_MAIN_GROUP] ?? $this->parent->getMainGroup());
            // is model
            $this->context[static::CONTEXT_AS_MODEL] = $this->parent->isModel();
            // create only
            $this->context[static::CONTEXT_DO_NOT_UPDATE] ??= $this->parent->isCreateOnly();
            $this->context[static::CONTEXT_DO_NOT_CREATE] ??= $this->parent->isUpdateOnly();
        }
        // Control
        if($this->context[static::CONTEXT_DO_NOT_UPDATE] && $this->context[static::CONTEXT_DO_NOT_CREATE]) {
            $message = vsprintf('Error %s line %d: for %s context, CONTEXT_DO_NOT_UPDATE and CONTEXT_DO_NOT_CREATE are both set to true!', [__METHOD__, __LINE__, $this->classname]);
            $this->addError($message, static::THROW_EXCEPTION_ON_ERROR_NOW);
        }
        // Define max depth if not
        $this->context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ??= true;
        // Define deep populate if not
        $this->context[AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE] ??= NormalizerServiceInterface::DEEP_POPULATE_MODE;
        if(isset($this->context[AbstractNormalizer::OBJECT_TO_POPULATE])) {
            // dump('EntityContainer::setContext(): OBJECT_TO_POPULATE', $this->context[AbstractNormalizer::OBJECT_TO_POPULATE]);
            // $object = $this->context[AbstractNormalizer::OBJECT_TO_POPULATE];
            // if($object->getClassname() === Uname::class && $object->getSelfState()->isNew()) {
            //     // New Uname
            //     dd($object, $object->getSelfState()->getReport(), $this->getInfo());
            // }
            $this->setEntity($this->context[AbstractNormalizer::OBJECT_TO_POPULATE]);
            unset($this->context[AbstractNormalizer::OBJECT_TO_POPULATE]);
        }
        return $this;
    }

    public function addContext(string $key, mixed $value): static
    {
        $this->context[$key] = $value;
        return $this;
    }

    public function removeContext(string $key): static
    {
        unset($this->context[$key]);
        return $this;
    }

    public function mergeContext(array $context, bool $replace = true): static
    {
        return $replace
            ? $this->setContext(array_merge($this->context, $context))
            : $this->setContext(array_merge($context, $this->context))
            ;
    }

    private static function getHiddenContextNames(): array
    {
        return [
            static::CONTEXT_MAIN_GROUP,
            static::CONTEXT_AS_MODEL,
            static::CONTEXT_DO_NOT_UPDATE,
            static::CONTEXT_DO_NOT_CREATE,
        ];
    }

    public function getDenormalizationContext(): array
    {
        $this->wireEm->surveyRecursion->survey(__METHOD__.'@'.spl_object_hash($this), 5, vsprintf('Error %s line %d: recursion limit reached!', [__METHOD__, __LINE__]));
        $d_context = $this->getContext();
        // Define groups if not
        if (empty($d_context[AbstractNormalizer::GROUPS] ?? [])) {
            $d_context[AbstractNormalizer::GROUPS] = NormalizerService::getDenormalizeGroups($this->getClassname(), $this->getMainGroup());
        }
        // Object to populate
        if(!empty($d_context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? null)) {
            $message = vsprintf('Error %s line %d: Object to populate should be empty! Got %s', [__METHOD__, __LINE__, Objects::toDebugString($d_context[AbstractNormalizer::OBJECT_TO_POPULATE])]);
            $this->addWarning($message);
        }
        $this->compileFinalData();
        if($this->hasEntity()) {
            $d_context[AbstractNormalizer::OBJECT_TO_POPULATE] = $this->getEntity();
        }
        if($d_context[AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE] && empty($d_context[AbstractNormalizer::OBJECT_TO_POPULATE])) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: DEEP_OBJECT_TO_POPULATE is set but OBJECT_TO_POPULATE is not set!%s- Si vous avez une structure imbriquée, les objets enfants seront écrasés par de nouvelles instances, sauf si vous définissez DEEP_OBJECT_TO_POPULATE sur true.', [__METHOD__, __LINE__, PHP_EOL]));
        }
        return array_filter($d_context, fn($key) => !in_array($key, static::getHiddenContextNames()), ARRAY_FILTER_USE_KEY);
    }

    private function isDeepPopulate(): bool
    {
        return $this->context[AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE];
    }


    /***********************************************************************************************
     * GROUPS
     **********************************************************************************************/

    public function setMainGroup(?string $main_group): static
    {
        if(empty($main_group)) {
            return $this->resetMainGroup();
        }
        if(!preg_match('/^[a-z0-9_]+$/', $main_group)) {
            $message = vsprintf('Error %s line %d: main group "%s" is invalid!', [__METHOD__, __LINE__, $main_group]);
            $this->addError($message, false);
        }
        $this->context[static::CONTEXT_MAIN_GROUP] = $main_group;
        return $this;
    }

    public function resetMainGroup(): static
    {
        return $this->setMainGroup(NormalizerService::MAIN_GROUP);
    }

    public function getMainGroup(): string
    {
        return $this->context[static::CONTEXT_MAIN_GROUP] ?? NormalizerService::MAIN_GROUP;
    }


    /***********************************************************************************************
     * OPTIONS
     **********************************************************************************************/

    // CREATE
    
    /**
     * Will persist new entity
     * 
     * @return bool
     */
    private function willPersist(): ?bool
    {
        if($this->isModel()) return false;
        return $this->isCreated() && $this->isCreateAllowed();
    }

    /**
     * Only create new entities - not update existing ones
     * 
     * @return bool
     */
    public function isCreateOnly(): bool
    {
        if($this->isModel()) return true;
        if($this->isCreateOnlyByRelation()) return true;
        return $this->context[static::CONTEXT_DO_NOT_UPDATE];
    }

    /**
     * Allows creation of new entities
     * 
     * @return bool
     */
    private function isCreateAllowed(): bool
    {
        if($this->isModel()) return true;
        return !$this->isCreateForbidden();
    }

    /**
     * Forbids creation of new entities - only update existing ones
     * 
     * @return bool
     */
    private function isCreateForbidden(): bool
    {
        if($this->isModel()) return false;
        if($this->isCreateOnlyByRelation()) return false;
        return $this->context[static::CONTEXT_DO_NOT_CREATE];
    }

    // UPDATE

    private function willUpdate(): ?bool
    {
        if($this->isModel()) return false;
        return $this->isLoaded() && $this->isUpdateAllowed();
    }

    /**
     * Requires entities to be loaded only - not created
     * 
     * @return bool
     */
    public function isUpdateOnly(): bool
    {
        if($this->isModel()) return false;
        if($this->isCreateOnlyByRelation()) return false;
        return $this->context[static::CONTEXT_DO_NOT_CREATE];
    }

    /**
     * Allows entities to be loaded or created
     * 
     * @return bool
     */
    private function isUpdateAllowed(): bool
    {
        if($this->isModel()) return false;
        return !$this->isUpdateForbidden();
    }

    /**
     * Forbids entities to be loaded - only created
     * 
     * @return bool
     */
    private function isUpdateForbidden(): bool
    {
        if($this->isModel()) return true;
        if($this->isCreateOnlyByRelation()) return true;
        return $this->context[static::CONTEXT_DO_NOT_UPDATE];
    }

    // UPDATE/FLUSH GRANTS

    private function isFlushable(): bool
    {
        return $this->willUpdate() || $this->willPersist();
    }

    private function isNotFlushable(): bool
    {
        return !$this->isFlushable();
    }


    private function isCreateOnlyByRelation(): bool
    {
        return $this->parent ? $this->parent->isCreated() && $this->parent->getRelationMapper()->isRelationCreateOnly($this->parentProperty) : false;
    }

    // private function isUpdateOnlyByRelation(): bool
    // {
    //     return $this->parent ? $this->parent->getRelationMapper()->isRelationUpdateOnly($this->parentProperty) : false;
    // }

    /**
     * Is create or update allowed
     * 
     * @return bool
     */
    public function isCreateOrUpdateAllowed(): bool
    {
        return $this->isCreateAllowed() || $this->isUpdateAllowed();
    }

    public function isModel(): ?bool
    {
        return $this->context[static::CONTEXT_AS_MODEL];
    }

    public function isEntity(): ?bool
    {
        return !$this->context[static::CONTEXT_AS_MODEL];
    }

    public function getParentProperty(): ?string
    {
        return $this->parentProperty ?? null;
    }

    public function isCompiled(): bool
    {
        return $this->compiled;
    }
 
    /***********************************************************************************************
     * ASSUME ARRAY ACCESS
     **********************************************************************************************/

    /** @param string $offset */
    public function offsetExists(mixed $offset): bool
    {    
        return isset($this->data[$offset]);
    }

    /** @param string $offset */
    public function offsetGet(mixed $offset): mixed
    {    
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException('Undefined property: ' . $offset);
        }
        return $this->data[$offset];
    }

    /** @param string $offset */
    public function offsetSet(mixed $offset, mixed $value): void
    {    
        $this->data[$offset] = $value;
    }

    /** @param string $offset */
    public function offsetUnset(mixed $offset): void
    {    
        unset($this->data[$offset]);
    }

    private function compileRawData(): void
    {
        $this->throwErrors();
        if(empty($this->rawdata)) {
            $this->wireEm->incDebugMode();
            $this->wireEm->surveyRecursion->survey(__METHOD__.'@'.spl_object_hash($this), 3, vsprintf('Error %s line %d: recursion limit reached!', [__METHOD__, __LINE__]));
            $dependencies = $this->getRelationMapper();
            if(!$this->isValid()) dd($this->controls->getMessages());
            foreach ($this->data as $property => $value) {
                if(in_array($property, $dependencies->getRelationFieldnames())) {
                    // Relation
                    switch (true) {
                        case $dependencies->isToOneRelation($property):
                            // ToOne relation
                            $value = [$value];
                            NormalizerService::UnhumanizeEntitiesYamlData($value);
                            $value = reset($value);
                            $value['classname'] = $this->resolveRelationClassnameByData($value, $property, $dependencies);
                            $value['shortname'] = Objects::getShortname($value['classname']);
                            $this->rawdata[$property] = $value;
                            break;
                        default:
                            // ToMany relation
                            assert($dependencies->isToManyRelation($property), vsprintf('Error %s line %d: relation %s is not valid, it should be array for multiple values!', [__METHOD__, __LINE__, $property]));
                            // $origin = $value;
                            NormalizerService::UnhumanizeEntitiesYamlData($value);
                            // dd($this->classname, $origin, $value);
                            $this->rawdata[$property] = [];
                            foreach($value as $val) {
                                $val['classname'] = $this->resolveRelationClassnameByData($val, $property, $dependencies);
                                $val['shortname'] = Objects::getShortname($val['classname']);
                                $this->rawdata[$property][] = $val;
                            }
                            break;
                    }
                } else if($property === static::EXTRA_DATA_NAME) {
                    // some extra data
                    $this->extradata = $value;
                } else {
                    // Column
                    $this->rawdata[$property] = $value;
                }
                $this->rawdata['classname'] = $this->classname;
                $this->rawdata['shortname'] = Objects::getShortname($this->classname);
                $this->tryFindEntity();
            }
            $this->globalControl();
            $this->wireEm->decDebugMode();
        }
    }

    private function compileFinalData(): void
    {
        if(!$this->compiled) {
            $this->wireEm->incDebugMode();
            $this->wireEm->surveyRecursion->survey(__METHOD__.'@'.spl_object_hash($this), 3, vsprintf('Error %s line %d: recursion limit reached!', [__METHOD__, __LINE__]));
            $this->compileRawData();
            $this->data = [];
            if($this->isValid()) {
                if(!$this->hasEntity()) {
                    // dump($this->getInfo());
                    $this->addError(vsprintf('Error %s line %d: Entity %s is not set!', [__METHOD__, __LINE__, $this->classname]), true);
                }
                $dependencies = $this->getRelationMapper();
                foreach ($this->rawdata as $property => $value) {
                    if(in_array($property, $dependencies->getRelationFieldnames())) {
                        // Relation
                        $context = $this->context;
                        if($dependencies->isToOneRelation($property)) {
                            if($related = $value['classname'] === Uname::class && $this->entity instanceof TraitUnamedInterface ? $this->entity->getUname() : null) {
                                $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $related;
                            }
                            $child = new static($this, $value['classname'], $value, $context, $property);
                            if($child->isValid()) {
                                $child->compile();
                                $this->data[$property] = $this->isDeepPopulate()
                                    ? $child
                                    : $child->getEntityDenormalized(); // --> assume populate by component itself - instead of denormalize
                            } else {
                                $message = vsprintf('Error %s line %d: toOne relation %s::%s is not valid or not found!', [__METHOD__, __LINE__, $this->__toString(), $property]);
                                $this->addError($message, false);
                                $this->data[$property] = null;
                            }
                            if(!$this->isDeepPopulate()) {
                                // if AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE is false:
                                // assume populate by component itself - instead of denormalize
                                $this->getRelationMapper()->setRelationValue($this->entity, $property, $this->data[$property]);
                                unset($this->data[$property]);
                            }
                        } else {
                            // ToMany relation
                            $this->data[$property] = new ArrayCollection();
                            foreach ($value as $val) {
                                $child = new static($this, $val['classname'], $val, $context, $property);
                                // assume populate by component itself - instead of denormalize
                                // --> AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE does not implements populate of collection
                                if($child->isValid()) {
                                    $entity_child = $child->getEntityDenormalized();
                                    if($entity_child && !$this->data[$property]->contains($entity_child)) {
                                        $this->data[$property]->add($entity_child);
                                    } else {
                                        $message = vsprintf('Error %s line %d: %s toMany relation %s::%s not found!', [__METHOD__, __LINE__, $this->classname, $property, $child->getShortname()]);
                                        $this->addError($message, true);
                                    }
                                } else {
                                    $message = vsprintf('Error %s line %d: %s toMany relation %s::%s not valid!', [__METHOD__, __LINE__, $this->classname, $property, $child->getShortname()]);
                                    $this->addError($message, true);
                                }
                            }
                            // dump($this->data[$property]);
                            $this->getRelationMapper()->setRelationValue($this->entity, $property, $this->data[$property]);
                            unset($this->data[$property]);
                        }
                    } else {
                        // Column
                        $this->data[$property] = $value;
                    }
                }
                $this->compiled = true;
            }
            $this->globalControl();
            $this->wireEm->decDebugMode();
        }
    }

    private function resolveRelationClassnameByData(
        array $data,
        string $property,
        RelationMapperInterface $dependencies,
    ): ?string
    {
        $availableClasses = $dependencies->getRelationTargetClasses($property, true);
        // $entity = isset($data['uname']) ? $this->normalizer->findEntityByUname($data['uname']['uname'] ?? $data['uname']) : null;
        if(isset($data['uname']['uname'])) {
            $classname = $this->normalizer->getClassnameByEuidOrUname($data['uname']['uname']);
        }
        $classname ??= $data['classname'] ?? null;
        if($classname) {
            if(!in_array($classname, $availableClasses)) {
                // dump($property, $data, $availableClasses);
                $message = vsprintf('Error %s line %d: relation entity classname %s is not valid for property %s of %s.%s- Please choose one of %s!', [__METHOD__, __LINE__, $classname, $property, $this->classname, PHP_EOL, implode(', ', $availableClasses)]);
                $this->addError($message, false);
            }
        } else {
            if(count($availableClasses) === 1) {
                $classname = reset($availableClasses);
            } else if(count($availableClasses) > 1) {
                // dump($property, $data, $availableClasses);
                $message = vsprintf('Error %s line %d: relation entity classname is not defined for property %s of %s.%s- Please choose one of %s!', [__METHOD__, __LINE__, $property, $this->classname, PHP_EOL, implode(', ', $availableClasses)]);
                $this->addError($message, false);
            }
        }
        return $classname;
    }

    private function globalControl(): void
    {
        if(!$this->settingControl) {
            // Before compilation
            // if($this->isCompiled() || $this->compiledControl) {
            //     throw new Exception(vsprintf('Error %s line %d: compileControl should NOT be done before settingControl!', [__METHOD__, __LINE__]));
            // }

            $this->settingControl = true;
        }
        if(!$this->compiledControl && $this->isCompiled()) {
            // After compilation
            if(!$this->settingControl) {
                throw new Exception(vsprintf('Error %s line %d: settingControl should be done before compileControl!', [__METHOD__, __LINE__]));
            }

            if(!$this->isValid()) {
                $this->normalizer->logger->error(vsprintf('Error %s line %d: EntityContainer %s has errors!', [__METHOD__, __LINE__, $this->getClassname()]));
            }
    
            $this->compiledControl = true;
        }
    }

    public function getInfo(
        string|array $groups = [],
        ?string $message = null
    ): array
    {
        $this->wireEm->surveyRecursion->survey(__METHOD__.'@'.spl_object_hash($this), 70, vsprintf('Error %s line %d: recursion limit reached!', [__METHOD__, __LINE__]));
        // $this->compileFinalData();
        $infos = [
            'main_message' => fn() => $message,
            'self_status' => [
                'valid' => fn() => $this->isValid(),
                'classname' => fn() => $this->getClassname(),
                'has_entity' => fn() => $this->hasEntity(),
                'is_max_level' => fn() => $this->isMaxLevel(),
                'parentProperty' => fn() => $this->getParentProperty(),
                'is_relation_with_parent' => fn() => $this->isRelationWithParent(),
                'is_raw_compiled' => fn() => !empty($this->rawdata),
                'is_compiled' => fn() => $this->isCompiled(),
                'is_root' => fn() => $this->isRoot(),
                'level' => fn() => $this->getLevel(),
                'parent_classnames' => fn() => $this->getClassnames(),
            ],
            'parent'.($this->getParent() ? ' L.'.$this->getParent()->getLevel() : ' [NO]') => fn() => $this->getParent() ? $this->getParent()->getInfo() : null,
            'raw_data' => fn() => $this->getRawdata(),
            'compiled-data' => fn() => $this->getCompiledData(),
            'context' => [
                'context' => fn() => $this->getContext(),
                'denormalization_context' => fn() => $this->getDenormalizationContext(),
            ],
            'entity' => [
                'classname' => fn() => $this->getClassname(),
                'created' => fn() => $this->isCreated(),
                'loaded' => fn() => $this->isLoaded(),
                'entity' => fn() => Objects::toDebugString($this->getEntity(), true)->__toString(),
                'entity_selfstate' => fn() => $this->getEntity()?->getSelfState()->getReport() ?? null,
            ],
            'options' => [
                'is_model' => fn() => $this->isModel(),
                'is_created' => fn() => $this->isCreated(),
                'is_loaded' => fn() => $this->isLoaded(),
                'is_relation_create_only' => fn() => $this->isCreateOnlyByRelation(),
                // 'is_relation_update_only' => fn() => $this->isUpdateOnlyByRelation(),
                'is_create_or_update_allowed' => fn() => $this->isCreateOrUpdateAllowed(),
                // new
                'willPersist' => fn() => $this->willPersist(),
                'isCreateOnly' => fn() => $this->isCreateOnly(),
                'isCreateAllowed' => fn() => $this->isCreateAllowed(),
                'isCreateForbidden' => fn() => $this->isCreateForbidden(),
                // update
                'willUpdate' => fn() => $this->willUpdate(),
                'isUpdateOnly' => fn() => $this->isUpdateOnly(),
                'isUpdateAllowed' => fn() => $this->isUpdateAllowed(),
                'isUpdateForbidden' => fn() => $this->isUpdateForbidden(),
                // flush
                'isFlushable' => fn() => $this->isFlushable(),
                'isNotFlushable' => fn() => $this->isNotFlushable(),
            ],
            'errors' => [
                'has_errors' => fn() => $this->controls->hasFail(),
                'relation_mapper_errors' => fn() => $this->relationMapper->getErrorMessages(),
            ],
            'messages' => [
                'errors' => fn() => $this->controls->getMessages('danger'),
                'warnings' => fn() => $this->controls->getMessages('warning'),
                'infos' => fn() => $this->controls->getMessages('info'),
                'undones' => fn() => $this->controls->getMessages('undone'),
                'success' => fn() => $this->controls->getMessages('success'),
            ],
        ];
        $finals = [];
        foreach ($infos as $name => $val_1) {
            if(is_array($val_1)) {
                foreach ($val_1 as $subname => $val_2) {
                    try { $finals[$name][$subname] = $val_2(); } catch (Throwable $th) { $finals[$name][$subname] = static::UNDEFINED_REPORT_VALUE; }
                }
            } else {
                try { $finals[$name] = $val_1(); } catch (Throwable $th) { $finals[$name] = static::UNDEFINED_REPORT_VALUE; }
            }
        }
        return empty($groups)
            ? $finals
            : array_filter($finals, fn($key) => in_array($key, (array)$groups), ARRAY_FILTER_USE_KEY)
            ;
    }

}