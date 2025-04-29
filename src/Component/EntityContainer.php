<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\EntityContainerInterface;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\RelationMapperInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
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

class EntityContainer implements EntityContainerInterface
{
    public const KEEP_INDEXES = false;

    protected readonly NormalizerServiceInterface $normalizer;
    protected readonly BaseEntityInterface $entity;
    public readonly RelationMapperInterface $relationMapper;
    public readonly WireEntityManagerInterface $wireEm;
    public readonly ?EntityContainerInterface $parent;
    public readonly int $level;
    protected array $rawdata = [];
    protected array $extradata = [];
    protected string $from;
    protected bool $compiled;
    protected Opresult $controls;

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
            $this->wireEm = $starter->wireEm;
            $this->level = 0;
            if(!empty($this->parentProperty)) {
                $message = vsprintf('Error %s line %d: Parent property should be empty for root container. Got %s', [__METHOD__, __LINE__, $this->parentProperty]);
                $this->addError($message, true);
            }
        } else {
            // Child container
            $this->parent = $starter;
            $this->normalizer = $this->parent->normalizer;
            $this->wireEm = $this->parent->wireEm;
            $this->level = $this->parent->getLevel() + 1;
            if(empty($this->parentProperty)) {
                $message = vsprintf('Error %s line %d: Parent property should not be empty for child container of parent entity %s.', [__METHOD__, __LINE__, $this->parent->getClassname()]);
                $this->addError($message, true);
            }
        }
        $this->from = static::FROMS[0];
        if(empty($this->data)) {
            $message = vsprintf('Error %s line %d: Data is empty', [__METHOD__, __LINE__]);
            $this->addError($message, true);
        }
        $this->relationMapper = $this->normalizer->getRelationMapper($this->classname);
        // if(!$this->relationMapper->isValid()) {
        //     throw new InvalidArgumentException(vsprintf('Error %s line %d: could no build RelationMappier for Class %s. Class is not valid.%sErrors:%s', [__METHOD__, __LINE__, $this->classname, PHP_EOL, PHP_EOL.$this->relationMapper->getMessagesAsString(false, false)]));
        // }
        // 2 - Options/contexts
        $this->mergeContext($context, false);
        // 3 - Data
        if($this->compileFinalData($this->data)) {
            $this->tryFindEntity();
        }
        if(count($this->rawdata) !== count($this->data)) {
            $message = vsprintf('Error %s line %d: Data and rawdata count does not match: Rawdata: %d / Data: %d.', [__METHOD__, __LINE__, count($this->rawdata), count($this->data)]);
            $this->addError($message, false);
        }
        if(!$this->isValid()) {
            $this->normalizer->logger->error(vsprintf('Error %s line %d: EntityContainer %s has errors!', [__METHOD__, __LINE__, $this->getClassname()]));
            // dump($this->getClassname().' has errors');
        }
    }

    public function __toString(): string
    {
        return Objects::getShortname($this).'@'.$this->classname;
    }

    public function isValid(): bool
    {
        return !$this->controls->hasFail();
    }

    private function addError(string $message, $trigger_exception = false): void
    {
        $this->controls->addError($message);
        if($trigger_exception || static::TRIGGER_EXCEPTION_ON_ERROR) {
            dump($this->getInfo());
            throw new Exception($message);
        }
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

    public function isProd(): bool
    {
        return $this->wireEm->appWire->isProd();
    }

    public function isDev(): bool
    {
        return $this->wireEm->appWire->isDev();
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
        return $this->level === 0;
    }
    
    public function getClassname(): string
    {
        return $this->classname;
    }

    public function getCompiledData(): array
    {
        return $this->data;
    }

    public function getRawdata(
        bool $withExtradata = false,
    ): array
    {
        return $withExtradata
            ? array_merge($this->rawdata, [static::EXTRA_DATA_NAME => $this->extradata])
            : $this->rawdata;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function isFromDb(): bool
    {
        return $this->from === 'db';
    }

    public function isFromYaml(): bool
    {
        return $this->from === 'yaml';
    }

    public function getRelationMapper(): RelationMapperInterface
    {
        return $this->relationMapper;
    }

    public function getInfo(): array
    {
        return [
            'self_status' => [
                'valid' => $this->isValid(),
                'level' => $this->level,
                'is_max_level' => $this->isMaxLevel(),
                'parentProperty' => $this->getParentProperty(),
                'is_compiled' => $this->isCompiled(),
            ],
            'internal_data' => [
                'raw_data' => $this->getRawdata(),
                'compiled-data' => $this->getCompiledData(),
            ],
            'context' => [
                'context' => $this->getContext(),
                'denormalization_context' => $this->getDenormalizationContext(),
            ],
            'entity' => [
                'classname' => $this->getClassname(),
                'from' => $this->getFrom(),
                'entity' => Objects::toDebugString($this->getEntity(), true)->__toString(),
                'entity_selfstate' => $this->getEntity()?->getSelfState()->getReport() ?? null,
                'is_create_only' => $this->isCreateOnly(),
                'is_relation_create_only' => $this->isRelationCreateOnly(),
                'is_model' => $this->isModel(),
            ],
            'errors' => [
                'has_errors' => $this->controls->hasFail(),
                'relation_mapper_errors' => $this->relationMapper->getErrorMessages(),
            ],
            'messages' => [
                'errors' => $this->controls->getMessages('danger'),
                'warnings' => $this->controls->getMessages('warning'),
                'infos' => $this->controls->getMessages('info'),
                'undones' => $this->controls->getMessages('undone'),
                'success' => $this->controls->getMessages('success'),
            ],
        ];
    }


    /***********************************************************************************************
     * ENTITY
     **********************************************************************************************/

    public function setEntity(BaseEntityInterface $entity): static
    {
        $this->checkEntityValidity($entity);
        $this->entity ??= $entity;
        $this->wireEm->insertEmbededStatus($this->entity);
        $this->from = $this->entity->getSelfState()->isLoaded() ? static::FROMS[1] : static::FROMS[0];
        if(!$this->isCreateOnly()) {
            $this->normalizer->addCreated($this->entity);
            // dump('- Added entity '.Objects::toDebugString($this->entity, false));
        } else {
            // dump('- NOT SAVED entity '.Objects::toDebugString($this->entity, false));
        }
        return $this;
    }

    public function getEntity(): ?BaseEntityInterface
    {
        return $this->entity ?? null;
    }

    public function getEntityDenormalized(?string $format = null, array $context = []): ?BaseEntityInterface
    {
        if($this->hasEntity()) {
            if(!empty($context)) {
                $this->mergeContext($context, true);
            }
            $this->checkEntityValidity();
            $entity = $this->normalizer->denormalizeEntity($this->getCompiledData(), $this->getClassname(), $format, $this->getDenormalizationContext());
            $this->finalizeEntity($entity);
            return $entity;
        }
        return null;
    }

    public function hasEntity(): bool
    {
        return isset($this->entity);
    }

    private function tryFindEntity(): bool
    {
        if(!$this->hasEntity()) {
            if($this->isCreateOrFind()) {
                // Try to find entity
                if(isset($this->rawdata['euid']) && ($entity = $this->normalizer->findEntityByEuid($this->rawdata['euid']))) {
                    $this->setEntity($entity);
                }
                if(isset($this->rawdata['uname']['uname']) && ($entity = $this->normalizer->findEntityByUname($this->rawdata['uname']['uname']))) {
                    $this->setEntity($entity);
                }
            }
            if(!$this->hasEntity()) {
                // Then create new entity
                $new = $this->wireEm->createEntity($this->classname, false);
                $this->setEntity($new);
            }
        }
        if($this->isCreateOnly() && $this->getEntity()->getSelfState()->isLoaded()) {
            // Entity is loaded, but is in create only context
            $this->addError(vsprintf('Error %s line %d: Entity %s is loaded as it is CREATE ONLY!', [__METHOD__, __LINE__, $this->getEntity()::class]), true);
        }
        return $this->hasEntity();
    }

    private function checkEntityValidity(?BaseEntityInterface $entity = null): void
    {
        $entity ??= $this->entity;
        if($this->isCreateOnly() && $entity->getSelfState()->isLoaded()) {
            $message = vsprintf('Error %s line %d: Entity %s is loaded, but in create only context!', [__METHOD__, __LINE__, $entity::class]);
            $this->addError($message, true);
        }
        if($this->hasEntity() && $this->entity !== $entity) {
            $message = vsprintf('Error %s line %d: Entity already set to %s', [__METHOD__, __LINE__, $this->entity::class]);
            $this->addError($message, true);
        }
        if(!is_a($entity->getClassname(), $this->classname, true)) {
            $message = vsprintf('Error %s line %d: Entity classname %s does not match container classname %s', [__METHOD__, __LINE__, $entity::class, $this->classname]);
            $this->addError($message, true);
        }
    }

    private function finalizeEntity(BaseEntityInterface $entity): void
    {
        if(!empty($this->extradata)) {
            // Apply extra data
            $this->normalizer->logger->warning(vsprintf('Applying extra data to entity %s: %s', [$this->classname, json_encode($this->extradata)]));
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
            $this->context[static::CONTEXT_CREATE_ONLY] ??= false;
        } else {
            // $this->mergeContext($this->parent->getContext(), false);
            // main group
            $this->setMainGroup($this->context[static::CONTEXT_MAIN_GROUP] ?? $this->parent->getMainGroup());
            // is model
            $this->context[static::CONTEXT_AS_MODEL] = $this->parent->isModel();
            // create only
            $this->context[static::CONTEXT_CREATE_ONLY] ??= $this->parent->isCreateOnly();
        }
        if(isset($this->context[AbstractNormalizer::OBJECT_TO_POPULATE])) {
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
            static::CONTEXT_CREATE_ONLY,
            static::CONTEXT_AS_MODEL,
        ];
    }

    public function getDenormalizationContext(): array
    {
        $context = $this->getContext();
        // Define groups if not
        if (empty($context[AbstractNormalizer::GROUPS] ?? [])) {
            $context[AbstractNormalizer::GROUPS] = NormalizerService::getDenormalizeGroups($this->getClassname(), $this->getMainGroup());
        }
        // Define max depth if not
        $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ??= true;
        // Object to populate
        if($this->isDev() && !empty($context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? null)) {
            $message = vsprintf('Error %s line %d: Object to populate should be empty! Got %s', [__METHOD__, __LINE__, Objects::toDebugString($context[AbstractNormalizer::OBJECT_TO_POPULATE])]);
            $this->addError($message, false);
        }
        if($this->hasEntity()) {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $this->getEntity();
        }
        return array_filter($context, fn($key) => !in_array($key, static::getHiddenContextNames()), ARRAY_FILTER_USE_KEY);
    }


    /***********************************************************************************************
     * GROUPS
     **********************************************************************************************/

    public function setMainGroup(?string $main_group): static
    {
        if(empty($main_group)) {
            return $this->resetMainGroup();
        }
        if(!preg_match('/^[a-z0-9_]+$/', $main_group) && !$this->isProd()) {
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
    
    public function isCreateOnly(): bool
    {
        return $this->context[self::CONTEXT_CREATE_ONLY]
            || $this->isModel()
            // If relation is create only
            || $this->isRelationCreateOnly()
            // || $this->classname === Uname::class
        ;
    }

    private function isRelationCreateOnly(): bool
    {
        return $this->parent?->getRelationMapper()->isRelationCreateOnly($this->parentProperty) ?? false;
    }

    public function isCreateOrFind(): bool
    {
        return !$this->isCreateOnly();
    }

    public function isModel(): bool
    {
        return $this->context[self::CONTEXT_AS_MODEL];
    }

    public function isEntity(): bool
    {
        return !$this->context[self::CONTEXT_AS_MODEL];
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
        $this->rawdata = [];
        $dependencies = $this->getRelationMapper();
        if(!$dependencies->isValid()) {
            $message = vsprintf('Error %s line %d: RelationMapper is not valid!%sErrors:%s', [__METHOD__, __LINE__, PHP_EOL, PHP_EOL.$dependencies->getMessagesAsString(false, false)]);
            $this->addError($message, false);
        }
        if(!$this->isValid()) return;
        foreach ($this->data as $property => $value) {
            if(in_array($property, $dependencies->getRelationFieldnames())) {
                // Relation
                switch (true) {
                    case $dependencies->isToOneRelation($property):
                        // ToOne relation
                        $value_array = [$value];
                        NormalizerService::UnhumanizeEntitiesYamlData($value_array);
                        $value = reset($value_array);
                        $value['classname'] = $this->resolveRelationClassnameByData($value, $property, $dependencies);
                        $value['shortname'] = Objects::getShortname($value['classname']);
                        $this->rawdata[$property] = $value;
                        break;
                    default:
                        // ToMany relation
                        assert($dependencies->isToManyRelation($property), vsprintf('Error %s line %d: relation %s is not valid, it should be array for multiple values!', [__METHOD__, __LINE__, $property]));
                        NormalizerService::UnhumanizeEntitiesYamlData($value);
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
        }
        $this->rawdata['classname'] = $this->classname;
        $this->rawdata['shortname'] = Objects::getShortname($this->classname);
    }

    private function compileFinalData(): bool
    {
        $this->compileRawData($this->data);
        // if(!$this->isValid()) return false;
        $this->data = [];
        $dependencies = $this->getRelationMapper();
        foreach ($this->rawdata as $property => $value) {
            if(in_array($property, $dependencies->getRelationFieldnames())) {
                // Relation
                if($dependencies->isToOneRelation($property)) {
                    $child = new static($this, $value['classname'], $value, [], $property);
                    if($child->isValid()) {
                        // dump('Set property '.$property.' to '.$this->classname);
                        $this->data[$property] = $child->getEntity();
                    } else {
                        $message = vsprintf('Error %s line %d: toOne relation %s::%s is not valid or not found!', [__METHOD__, __LINE__, $this->__toString(), $property]);
                        $this->addError($message, false);
                        $this->data[$property] = null;
                    }
                } else {
                    // ToMany relation
                    $this->data[$property] = new ArrayCollection();
                    foreach ($value as $val) {
                        $child = new static($this, $val['classname'], $val, [], $property);
                        if($child->isValid()) {
                            $child_entity = $child->getEntity();
                            if($child_entity && !$this->data[$property]->contains($child_entity)) $this->data[$property]->add($child_entity);
                        } else {
                            $message = vsprintf('Error %s line %d: one of toMany relations %s::%s is not valid or not found!', [__METHOD__, __LINE__, $this->__toString(), $property]);
                            $this->addError($message, false);
                        }
                    }
                }
            } else {
                // Column
                $this->data[$property] = $value;
            }
        }
        // dd($data);
        // $this->data = $data;
        $this->compiled = true;
        return true;
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

    // private function controlRelationValues(
    //     mixed $value,
    //     string $property,
    //     RelationMapperInterface $dependencies,
    // ): void
    // {
    //     $availableClasses = $dependencies->getRelationTargetClasses($property, true);
    //     if(!isset($value['classname']) && count($availableClasses) > 1) {
    //         if($dependencies->isRelationCreateOnly($property)) {
    //             $message = vsprintf('Error %s line %d: relation entity classname should be defined for property %s of %s.%s- Please choose one of %s!', [__METHOD__, __LINE__, $property, $this->classname, PHP_EOL, implode(', ', $availableClasses)]);
    //             throw new Exception($message);
    //         } else if(!isset($value['uname'])) {
    //             $message = vsprintf('Error %s line %d: relation for property %s of %s should have a uname to find it!', [__METHOD__, __LINE__, $property, $this->classname]);
    //             throw new Exception($message);
    //         }
    //     }
    //     $validClass = count($availableClasses) === 1;
    //     if(isset($value['classname'])) {
    //         foreach ($availableClasses as $class) {
    //             if($value['classname'] === $class) {
    //                 $validClass = true;
    //                 break;
    //             }
    //         }
    //     }
    //     if(!$validClass) {
    //         $message = vsprintf('Error %s line %d: relation entity classname %s is not valid for property %s of %s.%s- Please choose one of %s!', [__METHOD__, __LINE__, $value['classname'], $property, $this->classname, PHP_EOL, implode(', ', $availableClasses)]);
    //         throw new Exception($message);
    //     }
    //     if(!is_array($value)) {
    //         $message = vsprintf('Error %s line %d: relation %s should be an array! Got %s', [__METHOD__, __LINE__, $property, Objects::toDebugString($value)]);
    //         throw new Exception($message);
    //     }
    // }

}