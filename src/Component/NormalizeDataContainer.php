<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\NormalizeDataContainerInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\NormalizerService;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Objects;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
// Symfony
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
// PHP
use Exception;
use ReflectionProperty;

class NormalizeDataContainer implements NormalizeDataContainerInterface
{
    public const STD_ASSOCIATIONS_MAX_LEVEL = 5;
    public const MODELS_ASSOCIATIONS_MAX_LEVEL = 1;
    public const UNSERIALIZABLE_FIELDS = ['uname'];

    // contexts
    public const CONTEXT_MAIN_GROUP = 'context_main_group';
    public const CONTEXT_CREATE_ONLY = 'context_create_only';
    public const CONTEXT_AS_MODEL = 'context_as_model';

    public readonly NormalizerServiceInterface $normService;
    public readonly WireEntityManagerInterface $wireEm;
    public readonly ?NormalizeDataContainerInterface $parent;
    public readonly int $level;
    public BaseEntityInterface $entity;
    protected array $addPostRelations = [];
    // public readonly ClassMetadata $classMetadata;
    public readonly string $classname;
    protected array $context = [];
    protected PropertyAccessorInterface $accessor;
    protected array $data;
    protected ?string $uname = null;
    protected array $reverse_operations = []; // description in data['_reverse'] = [...]

    public function __construct(
        NormalizerServiceInterface|NormalizeDataContainerInterface $starter,
        string|BaseEntityInterface $classOrEntity,
        array $data,
        array $context = []
    )
    {
        $this->accessor ??= PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        // 1 - Start Container
        if($starter instanceof NormalizerServiceInterface) {
            // First initialization - is root container
            $this->parent = null;
            $this->normService = $starter;
            $this->wireEm = $starter->wireEm;
            $this->level = 0;
        } else {
            // Child container
            $this->parent = $starter;
            $this->normService = $this->parent->normService;
            $this->wireEm = $this->parent->wireEm;
            $this->level = $this->parent->getLevel() + 1;
        }
        // 2 - Entity classname
        if($classOrEntity instanceof BaseEntityInterface) {
            // classOrEntity is entity
            $this->classname = $classOrEntity->getClassname();
            $this->setEntity($classOrEntity);
        } else {
            // classOrEntity is string classname
            if(!$this->wireEm->entityExists($classOrEntity, false, true)) {
                $resolveds = $this->wireEm->resolveFinalEntitiesByNames([$classOrEntity], true);
                if(count($resolveds) === 1) {
                    $classOrEntity = reset($resolveds);
                } else {
                    throw new Exception(vsprintf('Error %s line %d: entity %s does not exist! Could it be one of these?%s', [__METHOD__, __LINE__, $classOrEntity, PHP_EOL.'- '.implode(PHP_EOL.'- ', $resolveds)]));
                }
            }
            $this->classname = $classOrEntity;
            if(!$this->wireEm->entityExists($classOrEntity, false, true)) {
                throw new Exception(vsprintf('Error %s line %d: entity %s does not exist!', [__METHOD__, __LINE__, $classOrEntity]));
            }
        }
        // 3 - Options/contexts
        $this->mergeContext($context, false);
        // 4 - Data
        $this->setData($data);

        // DEBUG
        if($this->isRoot()) {
            // dump('Data after initialization (line '.__LINE__.'):', $this, $this->getDenormalizationContext());
        } else {
            // dump('Child '.$this->getType().' context :', $this->getDenormalizationContext());
        }
        // dump($this->getDenormalizationContext());
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
        $max = $this->isModel()
            ? static::MODELS_ASSOCIATIONS_MAX_LEVEL
            : static::STD_ASSOCIATIONS_MAX_LEVEL
            ;
        return $this->level >= $max;
    }

    public function isRoot(): bool
    {
        return $this->level === 0;
    }


    /***********************************************************************************************
     * TYPE / CLASSNAME
     **********************************************************************************************/

    public function getType(): string
    {
        $this->controlContainer(true);
        return $this->classname;
    }


    /***********************************************************************************************
     * CONTEXT
     **********************************************************************************************/

    protected function setEntity(BaseEntityInterface $entity): static
    {
        if($this->hasEntity() && $this->entity !== $entity) {
            throw new Exception(vsprintf('Error %s line %d: entity %s is already set!%sCan not set another entity %s', [__METHOD__, __LINE__, Objects::toDebugString($this->entity), PHP_EOL, Objects::toDebugString($entity)]));
        }
        $this->entity ??= $entity;
        if($this->entity instanceof BaseEntityInterface && $this->entity->getSelfState()->isLoaded()) {
            $this->wireEm->insertEmbededStatus($this->entity);
        }
        // Set Uname name
        if($this->entity instanceof TraitUnamedInterface && Encoders::isUnameFormatValid($this->uname) && $this->entity->getSelfState()->isNew()) {
            $this->entity->setUname($this->uname);
        }
        $this->controlContainer(true);
        // dump($this->entity);
        return $this;
    }

    public function finalizeEntity(): bool
    {
        if($this->isMaxLevel()) {
            return false;
        }
        if(!$this->hasEntity()) {
            throw new Exception(vsprintf('Error %s line %d: entity is not set!', [__METHOD__, __LINE__]));
        }
        // $this->setEntity($entity);
        $this->applyPostRelations();
        // Apply reverse operations
        $this->applyReverseOperations();
        return true;
    }

    public function getEntity(): ?BaseEntityInterface
    {
        if($this->isMaxLevel()) {
            return null;
        }
        if(!isset($this->entity) && !$this->findEntity()) {
            $this->setEntity($this->wireEm->createEntity($this->getType()));
        }
        return $this->entity;
    }

    public function hasEntity(): bool
    {
        return !empty($this->entity ?? null);
    }


    /***********************************************************************************************
     * CONTEXT
     **********************************************************************************************/

    public function getContext(): array
    {
        $this->controlContainer(true);
        return $this->context;
    }

    public function setContext(
        array $context
    ): static
    {
        $this->context = $context;
        if($this->isRoot()) {
            // main group
            $this->setMainGroup($this->context[self::CONTEXT_MAIN_GROUP] ?? null);
            // is model
            $this->context[self::CONTEXT_AS_MODEL] ??= false;
            // create only
            $this->context[self::CONTEXT_CREATE_ONLY] ??= false;
        } else {
            // $this->mergeContext($this->parent->getContext(), false);
            // main group
            $this->setMainGroup($this->context[self::CONTEXT_MAIN_GROUP] ?? $this->parent->getMainGroup());
            // is model
            $this->context[self::CONTEXT_AS_MODEL] = $this->parent->isModel();
            // create only
            $this->context[self::CONTEXT_CREATE_ONLY] ??= $this->parent->isCreateOnly();
        }
        $this->controlContainer(true);
        return $this;
    }

    public function addContext(
        string $key,
        mixed $value
    ): static
    {
        $this->context[$key] = $value;
        return $this;
    }

    public function removeContext(
        string $key
    ): static
    {
        unset($this->context[$key]);
        return $this;
    }

    public function mergeContext(
        array $context,
        bool $replace = true
    ): static
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

    // public function getNormalizationContext(): array
    // {
    //     $this->controlContainer(true);
    //     $context = $this->getContext();
    //     // Define groups if not
    //     if (empty($context[AbstractNormalizer::GROUPS] ?? [])) {
    //         $context[AbstractNormalizer::GROUPS] = NormalizerService::getNormalizeGroups($this->getType(), $this->getMainGroup());
    //     }
    //     $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ??= true;
    //     return array_filter($context, fn($key) => !in_array($key, static::getHiddenContextNames()), ARRAY_FILTER_USE_KEY);
    // }

    public function getDenormalizationContext(): array
    {
        $this->controlContainer(true);
        $context = $this->getContext();
        // Define groups if not
        if (empty($context[AbstractNormalizer::GROUPS] ?? [])) {
            $context[AbstractNormalizer::GROUPS] = NormalizerService::getDenormalizeGroups($this->getType(), $this->getMainGroup());
        }
        $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ??= true;
        // Object to populate
        $context[AbstractNormalizer::OBJECT_TO_POPULATE] ??= $this->getEntity();
        return array_filter($context, fn($key) => !in_array($key, static::getHiddenContextNames()), ARRAY_FILTER_USE_KEY);
    }


    /***********************************************************************************************
     * GROUPS
     **********************************************************************************************/

     public function setMainGroup(
        ?string $main_group
    ): static
    {
        if(empty($main_group)) {
            return $this->resetMainGroup();
        }
        if(!preg_match('/^[a-z0-9_]+$/', $main_group) && !$this->isProd()) {
            throw new Exception(vsprintf('Error %s line %d: main group "%s" is invalid!', [__METHOD__, __LINE__, $main_group]));
        }
        $this->context[self::CONTEXT_MAIN_GROUP] = $main_group;
        return $this;
    }

    public function resetMainGroup(): static
    {
        return $this->setMainGroup(NormalizerService::MAIN_GROUP);
    }

    public function getMainGroup(): string
    {
        return $this->context[self::CONTEXT_MAIN_GROUP] ?? NormalizerService::MAIN_GROUP;
    }


    /***********************************************************************************************
     * OPTIONS
     **********************************************************************************************/
    
    public function isCreateOnly(): bool
    {
        return $this->context[self::CONTEXT_CREATE_ONLY]
            || $this->isModel()
            || is_a($this->classname, UnameInterface::class, true)
        ;
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


    /***********************************************************************************************
     * DATA
     **********************************************************************************************/

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(
        array $data
    ): static
    {
        if(empty($data) && $this->isRoot()) {
            throw new Exception(vsprintf('Error %s line %d: data can not be empty while is root!', [__METHOD__, __LINE__]));
        }
        // keep & remove specific data
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'uname':
                    $this->uname = $value;
                    break;
                case '_reverse':
                    $this->reverse_operations = $value;
                    break;
                default:
                    //
                    break;
            }
        }
        $this->data = $this->regularizeData($data);
        $this->getEntity();
        return $this;
    }

    /**
     * Regularize data
     * - in $dataLvl 0, data is array of [field => value(s)]
     * 
     * @param array $data
     * @return array|null
     */
    protected function regularizeData(
        array $data,
    ): array|null
    {
        if($this->isMaxLevel()) {
            // Max level reached
            return null;
        }
        // Data of ONE entity - list of fields and values
        if(empty($data)) {
            throw new Exception(vsprintf('Error %s line %d: data for entity can not be empty!', [__METHOD__, __LINE__]));
        }
        // 1 - Columns
        $data['classname'] ??= $this->classname;
        if($data['classname'] !== $this->classname) {
            // Classname is not the same as the container
            throw new Exception(vsprintf('Error %s line %d: classname "%s" is not the same as the container "%s"!', [__METHOD__, __LINE__, $data['classname'], $this->classname]));
        }
        $data['shortname'] ??= Objects::getShortname($data['classname']);
        if($data['shortname'] !== Objects::getShortname($data['classname'])) {
            // Shortname is not the same as the container
            throw new Exception(vsprintf('Error %s line %d: shortname "%s" is not the same as the classname "%s" (%s)!', [__METHOD__, __LINE__, $data['shortname'], Objects::getShortname($data['classname']), $data['classname']]));
        }
        // 2 - Relations
        $mappings = $this->getAssociationMappings(array_keys($data));
        // dump($mappings, array_keys($data));
        if(empty($mappings)) {
            // No relations
            return $data;
        }
        foreach ($data as $field => $value) {
            if(array_key_exists($field, $mappings)) {
                // Relation
                /** @var AssociationMapping $mapping */
                $mapping = $mappings[$field]['mapping'];
                $create = $mapping->isCascadePersist() || $mapping->orphanRemoval;
                if($mapping->isToOne()) {
                    // ToOne relation
                    # find identifier if exists
                    if(is_array($value)) {
                        $key = array_key_first($value);
                        if(count($value) === 1 && (is_string($key) || (is_int($key) && $key > 0)) && is_array(reset($value))) {
                            $value = $this->scalarToArrayUid($key, reset($value));
                        }
                    } else {
                        $value = $this->scalarToArrayUid($value);
                    }
                    if($new_data = $this->regularizeRelation($value, $mappings[$field])) {
                        $ndc = new static($this, $new_data['classname'], $new_data);
                        if($create) {
                            // Create only
                            $ndc->setContext([static::CONTEXT_CREATE_ONLY => false]);
                        }
                        $data[$field] = $ndc;
                    }
                } else {
                    // ToMany relation
                    $collection = new ArrayCollection();
                    foreach($value as $name => $sub) {
                        $sub = is_array($sub) ? $this->scalarToArrayUid($name, $sub) : $this->scalarToArrayUid($sub);
                        if($new_data = $this->regularizeRelation($sub, $mappings[$field])) {
                            $ndc = new Static($this, $new_data['classname'], $new_data);
                            if($create) {
                                // Create only
                                $ndc->setContext([static::CONTEXT_CREATE_ONLY => false]);
                            }
                            $entity = $this->normService->denormalizeEntity($ndc, $ndc->getType(), null, $ndc->getDenormalizationContext());
                            if($entity && !$collection->contains($entity)) {
                                $ndc->finalizeEntity();
                                $collection->add($entity);
                            }
                        }
                    }
                    if(!$collection->isEmpty()) {
                        $this->addPostRelation($field, $collection);
                    }
                    unset($data[$field]);
                }
            } else {
                // Column
                // $data[$field] = ...;
            }
        }
        return $data;
    }

    protected function regularizeRelation(
        null|string|int|iterable $data,
        array $mapping
    ): mixed
    {
        $valid_requires = array_filter($mapping['require'], fn($class) => $this->wireEm->entityExists($class, true, true));
        // 1 - classname
        $classenames = [
            // 'shortname' => isset($data['shortname']) ? $this->wireEm->getClassnameByShortname($data['shortname']) : null,
            'classname' => $data['classname'] ?? reset($valid_requires),
            // 'mapping' => $mapping['require'],
        ];
        // dump($classenames, $valid_requires);
        if(count($mapping['require']) === 1 || !empty($classenames['classname'])) {
            // Default classname
            $data['classname'] ??= reset($mapping['require']);
            if(!$this->wireEm->entityExists($data['classname'], true, true)) {
                throw new Exception(vsprintf('Error %s line %d: classname "%s" is not valid!', [__METHOD__, __LINE__, $data['classname']]));
            }
        } else if(is_array($mapping['require'])) {
            // List of classnames
            if(!isset($classenames['classname']) || empty($classenames['classname'])) {
                throw new Exception(vsprintf('Error %s line %d:%sfor %s relation field "%s", classname is not defined, please use one of %s!', [__METHOD__, __LINE__, PHP_EOL, $this->classname, $mapping['property'], implode(', ', $mapping['require'])]));
            }
            if(!in_array($classenames['classname'], $mapping['require'])) {
                // Classname is not in the list
                throw new Exception(vsprintf('Error %s line %d:%sfor %s relation field "%s", classname "%s" is not in the list of available classnames %s!', [__METHOD__, __LINE__, PHP_EOL, $this->classname, $mapping['property'], $data['classname'], implode(', ', $mapping['require'])]));
            }
        }
        $data['shortname'] ??= Objects::getShortname($data['classname']);
        return !empty($data) ? $data : null;
    }

    /**
     * Convert scalar value to array of uid
     * @param mixed $value
     * @param array $dataToMerge
     * @return array|null
     */
    protected function scalarToArrayUid(
        mixed $value,
        array $dataToMerge = []
    ): array|null
    {
        if($value === "0") $value = null;
        if(!empty($value) && is_string($value) || is_int($value)) {
            if(Encoders::isEuidFormatValid($value)) {
                $value = ['euid' => $value];
            } else if(Encoders::isUnameFormatValid($value)) {
                $value = ['uname' => $value];
            } else if(preg_match('/^\d+$/', (string)$value) && intval($value) > 0) {
                $value = ['id' => intval($value)];
            }
        }
        if(!empty($dataToMerge) && is_array($value)) {
            $value = array_merge($value, $dataToMerge);
        }
        return is_array($value) && !empty($value) ? $value : null;
    }


    /***********************************************************************************************
     * CLASSMETADATA / MAPPINGS / CONTROLS
     **********************************************************************************************/

    protected function getAssociationMappings(
        array $fields = []
    ): array
    {
        $final_mappings = $this->wireEm->getAllRelatedProperties($this->getType(), $fields, function(AssociationMapping $mapping, string $field, ClassMetadata $cmd): bool {
            return !in_array($field, static::UNSERIALIZABLE_FIELDS);
        });
        return $final_mappings;
    }

    // protected function getClassMetadata(): ?ClassMetadata
    // {
    //     return $this->classMetadata ??= $this->wireEm->getClassMetadata($this->getType());
    // }


    /**
     * Try find entity with data if exists
     */
    protected function findEntity(): bool
    {
        if(!$this->hasEntity() && $this->isCreateOrFind()) {
            $entity = null;
            // if(!$this->isProd() && !$this->wireEm->isDebugMode()) {
            //     throw new Exception(vsprintf('Error %s line %d: debug mode (%d) must be enabled!', [__METHOD__, __LINE__, $this->wireEm->debug_mode]));
            // }
            $this->wireEm->incDebugMode(); // --> should be unnecessary
            // Try find entity if exists
            if (!empty($this->data['id'] ?? null)) {
                $repo = $this->wireEm->getRepository($this->getType());
                $entity = $repo->find($this->data['id']);
                if(empty($entity)) {
                    throw new Exception(vsprintf('Error %s line %d: entity with id "%s" should exist, but was not found!', [__METHOD__, __LINE__, $this->data['id']]));
                }
            }
            if (!$entity && Encoders::isEuidFormatValid($this->data['euid'] ?? null)) {
                $entity = $this->wireEm->findEntityByEuid($this->data['euid']);
                if(empty($entity)) {
                    throw new Exception(vsprintf('Error %s line %d: entity with euid "%s" should exist, but was not found!', [__METHOD__, __LINE__, $this->data['euid']]));
                }
            }
            if (!$entity && Encoders::isUnameFormatValid($this->uname)) {
                $entity = $this->wireEm->findEntityByUname($this->uname);
                if(empty($entity) && empty($this->data)) {
                    throw new Exception(vsprintf('Error %s line %d: entity with uname "%s" should exist, but was not found!', [__METHOD__, __LINE__, $this->uname]));
                }
            }
            $this->wireEm->decDebugMode(); // --> should be unnecessary
            if($entity instanceof BaseEntityInterface) {
                $this->setEntity($entity);
            }
        }
        return $this->hasEntity();
    }


    /***********************************************************************************************
     * REVERSE OPERATIONS
     * (apply AFTER denormalization)
     **********************************************************************************************/

    public function getReverseOperations(): array
    {
        return $this->reverse_operations;
    }

    public function hasReverseOperations(): bool
    {
        return !empty($this->reverse_operations);
    }

    public function applyReverseOperations(): void
    {
        foreach ($this->reverse_operations as $targetUname => $targetPropertys) {
            foreach ((array)$targetPropertys as $targetProperty) {
                $targetEntity = $this->wireEm->findEntityByUname($targetUname);
                if($targetEntity instanceof BaseEntityInterface) {
                    // $targetValue = $this->accessor->getValue($targetEntity, $targetProperty);
                    // if($targetValue instanceof ArrayCollection) {
                    //     if(!$targetValue->contains($this->entity))
                    //     $targetValue->add($this->entity);
                    // }
                    /**
                     * @see https://symfony.com/doc/current/components/property_access.html#using-non-standard-adder-remover-methods
                     */
                    $this->accessor->setValue($targetEntity, $targetProperty, $this->entity);
                } else {
                    // Entity not found
                    throw new Exception(vsprintf('Error %s line %d: entity with uname "%s" should exist, but was not found!', [__METHOD__, __LINE__, $targetUname]));
                }
            }
        }
    }


    protected function setFieldValue(
        string $field,
        mixed $value
    ): static
    {
        $reflfield = $this->wireEm->getClassMetadata($this->getType())->reflFields[$field] ?? null;
        if($reflfield instanceof ReflectionProperty) {
            $this->wireEm->getClassMetadata($this->getType())->setFieldValue($this->entity, $reflfield->name, $value);
        } else {
            $this->accessor ??= PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
            $this->accessor->setValue($this->entity, $field, $value);
        }
        return $this;
    }

    protected function addPostRelation(
        string $field,
        BaseEntityInterface|Collection $value
    ): static
    {
        $this->addPostRelations[$field] = $value;
        return $this;
    }

    protected function applyPostRelations(): static
    {
        foreach ($this->addPostRelations as $field => $values) {
            if($this->entity instanceof BaseEntityInterface) {
                $this->setFieldValue($field, $values);
            }
        }
        $this->addPostRelations = [];
        return $this;
    }

    /**
     * Get list of error messages if container is not valid
     * 
     * @param bool $exception
     */
    protected function controlContainer(
        bool $exception = false
    ): array
    {
        if($this->isProd()) return [];
        $error_messages = [];
        foreach (array_keys($this->context) as $key) {
            if(!is_string($key)) {
                $error_messages[] = vsprintf('Error %s line %d: context key %s must be a string!', [__METHOD__, __LINE__, (string)$key]);
            }
        }
        if (empty($this->classname)) {
            $error_messages[] = vsprintf('Error %s line %d: context type is not defined!', [__METHOD__, __LINE__]);
        }
        if(is_string($this->classname) && !class_exists($this->classname) && !interface_exists($this->classname)) {
            $error_messages[] = vsprintf('Error %s line %d: class %s does not exist!', [__METHOD__, __LINE__, $this->classname]);
        }
        if(!$this->wireEm->entityExists($this->classname, false, true)) {
            $error_messages[] = vsprintf('Error %s line %d: class %s does not implement %s!', [__METHOD__, __LINE__, $this->classname, BaseEntityInterface::class]);
        }
        // control data
        if(isset($this->data)) {
            if(!is_array($this->data)) {
                $error_messages[] = vsprintf('Error %s line %d: data must be an array!', [__METHOD__, __LINE__]);
            }
            if(isset($this->data['uname']) && !is_string($this->data['uname'])) {
                $error_messages[] = vsprintf('Error %s line %d: data uname must be a string!', [__METHOD__, __LINE__]);
            }
        }
        // controle entity
        if($this->hasEntity()) {
            // Has entity
            if(!is_a($this->entity, $this->classname)) {
                dump($this);
                $error_messages[] = vsprintf('Error %s line %d: entity %s does not implement %s!', [__METHOD__, __LINE__, $this->entity->getClassname(), $this->classname]);
            }
            if($this->isCreateOnly() && !empty($this->entity->getId())) {
                dump($this);
                $error_messages[] = vsprintf('Error %s line %d: %s is already persisted!', [__METHOD__, __LINE__, $this->entity]);
            }
        }
        if($exception && count($error_messages) > 0) {
            throw new Exception(vsprintf('Error messages in %s control:%s%s', [static::class, PHP_EOL.'- ', implode(PHP_EOL.'- ', $error_messages)]));
        }
        return $error_messages;
    }


}
