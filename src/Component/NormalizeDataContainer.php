<?php

namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Attribute\SerializationMapping;
use Aequation\WireBundle\Component\interface\NormalizeDataContainerInterface;
use Aequation\WireBundle\Entity\interface\BetweenManyInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\NormalizerService;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Objects;
use Doctrine\Common\Collections\ArrayCollection;
// Symfony
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
// PHP
use Exception;
use ReflectionProperty;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

class NormalizeDataContainer implements NormalizeDataContainerInterface
{
    public const MODELS_NO_ASSOCIATIONS = true;

    public readonly WireEntityInterface $entity;
    public readonly ClassMetadata $classMetadata;
    public readonly string $classname;
    protected string $main_group;
    protected readonly bool $create_only;
    protected PropertyAccessorInterface $accessor;
    protected array $data;
    protected ?string $uname = null;
    protected array $reverse_operations = []; // description in data['_reverse'] = [...]

    public function __construct(
        protected readonly WireEntityManagerInterface $wireEm,
        string|WireEntityInterface $classOrEntity,
        array $data,
        protected array $context = [],
        ?string $main_group = null,
        bool $create_only = true,
        protected readonly bool $is_model = false,
    ) {
        if($classOrEntity instanceof WireEntityInterface) {
            // is object entity
            $this->entity = $classOrEntity;
            $this->classname = $classOrEntity->getClassname();
        } else {
            // is string
            dd($this->wireEm->getEntityNames(true), $this->wireEm->getBetweenEntityNames(true), $this->wireEm->getTranslationEntityNames(true));
            if(!$this->wireEm->entityExists($classOrEntity)) {
                $resolved = $this->wireEm->getEntityClassesOfInterface([$classOrEntity], false, true);
                dd($resolved, $classOrEntity);
                if(count($resolved) === 1) {
                    $classOrEntity = reset($resolved);
                } else {
                    throw new Exception(vsprintf('Error %s line %d: entity %s does not exist! Could it be one of these?%s', [__METHOD__, __LINE__, $classOrEntity, PHP_EOL.'- '.implode(PHP_EOL.'- ', $resolved)]));
                }
            }
            $this->classname = $classOrEntity;
        }
        $this->setMainGroup((string)$main_group);
        $this->defineCreateOnly($create_only);
        $this->setData($data);
    }

    public function isProd(): bool
    {
        return $this->wireEm->appWire->isProd();
    }

    public function isDev(): bool
    {
        return $this->wireEm->appWire->isDev();
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

    protected function setEntity(
        WireEntityInterface $entity
    ): static
    {
        if($this->hasEntity() && $this->entity !== $entity) {
            throw new Exception(vsprintf('Error %s line %d: entity %s is already set!%sCan not set another entity %s', [__METHOD__, __LINE__, Objects::toDebugString($this->entity), PHP_EOL, Objects::toDebugString($entity)]));
        }
        $this->entity ??= $entity;
        if($this->entity->getSelfState()->isLoaded()) {
            $this->wireEm->insertEmbededStatus($this->entity);
        }
        // Set Uname name
        if(Encoders::isUnameFormatValid($this->uname) && $this->entity->getSelfState()->isNew() && $this->entity instanceof TraitUnamedInterface) {
            $this->entity->setUname($this->uname);
        }
        $this->controlContainer(true);
        // dump($this->entity);
        return $this;
    }

    public function finalizeEntity(WireEntityInterface $entity): bool
    {
        if($this->hasEntity() && $this->entity !== $entity) {
            return false;
        }
        $this->setEntity($entity);
        // Apply reverse operations
        $this->applyReverseOperations();
        return true;
    }

    public function getEntity(): ?WireEntityInterface
    {
        return $this->entity ?? null;
    }

    public function hasEntity(): bool
    {
        return isset($this->entity);
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

    public function getNormalizationContext(): array
    {
        $this->controlContainer(true);
        $context = $this->context;
        // Define groups if not
        if (empty($context[AbstractNormalizer::GROUPS] ?? [])) {
            $context[AbstractNormalizer::GROUPS] = NormalizerService::getNormalizeGroups($this->getType(), $this->getMainGroup());
        }
        $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ??= true;
        return $context;
    }

    public function getDenormalizationContext(): array
    {
        $this->controlContainer(true);
        $context = $this->context;
        // Define groups if not
        if (empty($context[AbstractNormalizer::GROUPS] ?? [])) {
            $context[AbstractNormalizer::GROUPS] = NormalizerService::getDenormalizeGroups($this->getType(), $this->getMainGroup());
        }
        $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ??= true;
        // Object to populate
        $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $this->findEntity()
            ? $this->getEntity()
            : $this->wireEm->createEntity($this->getType());
        return $context;
    }


    /***********************************************************************************************
     * GROUPS
     **********************************************************************************************/

     public function setMainGroup(
        string $main_group
    ): static
    {
        if(empty($main_group)) {
            return $this->resetMainGroup();
        }
        if(!preg_match('/^[a-z0-9_]+$/', $main_group) && !$this->isProd()) {
            throw new Exception(vsprintf('Error %s line %d: main group "%s" is invalid!', [__METHOD__, __LINE__, $main_group]));
        }
        $this->main_group = $main_group;
        return $this;
    }

    public function resetMainGroup(): static
    {
        return $this->setMainGroup(NormalizerService::MAIN_GROUP);
    }

    public function getMainGroup(): string
    {
        return $this->main_group;
    }


    /***********************************************************************************************
     * OPTIONS
     **********************************************************************************************/
    
    protected function defineCreateOnly(
        bool $create_only
    ): static
    {
        $this->create_only = $create_only
            || $this->isModel()
            || is_a($this->classname, UnameInterface::class, true)
            ;
        return $this;
    }

    public function isCreateOnly(): bool
    {
        return $this->create_only;
    }

    public function isCreateOrFind(): bool
    {
        return !$this->create_only;
    }

    public function isModel(): bool
    {
        return $this->is_model;
    }

    public function isEntity(): bool
    {
        return !$this->is_model;
    }

    public function getOptions(): array
    {
        return [
            'create_only' => $this->isCreateOnly(),
            'is_model' => $this->isModel(),
        ];
    }


    /***********************************************************************************************
     * DATA
     **********************************************************************************************/

    public function getData(): array
    {
        return $this->getCompiledData();
    }

    public function setData(
        array $data
    ): static
    {
        if(empty($data)) {
            throw new Exception(vsprintf('Error %s line %d: data can not be empty!', [__METHOD__, __LINE__]));
        }
        $this->data = [];
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'uname':
                    $this->uname = $value;
                    break;
                case '_reverse':
                    $this->reverse_operations = $value;
                    break;
                default:
                    $this->data[$key] = $value;
                    break;
            }
        }
        return $this;
    }

    protected function scalarToArrayUid(
        mixed $value
    ): array|false
    {
        if(is_string($value) || is_int($value)) {
            if(Encoders::isEuidFormatValid($value)) {
                $value = ['euid' => $value];
            } else if(Encoders::isUnameFormatValid($value)) {
                $value = ['uname' => $value];
            } else if(preg_match('/^\d+$/', (string)$value) && intval($value) > 0) {
                $value = ['id' => intval($value)];
            }
        }
        return is_array($value) && !empty($value) ? $value : false;
    }

    protected function toArrayList(
        array|string|int $data,
        bool $isMany
    ): array
    {
        $mem_data = $data;
        switch (true) {
            case is_array($data) && array_is_list($data):
                if(!$isMany) {
                    throw new Exception(vsprintf('Error %s line %d: value %s is not available: $isMany should be true to get multiple entities!', [__METHOD__, __LINE__, Objects::toDebugString($mem_data)]));
                }
                // keys are NOT identifiers
                foreach ($data as $key => $value) {
                    $data[$key] = $this->toArrayList($value, true);
                }
                break;
            case is_array($data) && $isMany:
                // keys are identifiers
                foreach ($data as $key => $value) {
                    $data[$key] = array_merge($this->toArrayList($key, true), $value);
                }
                break;
            case is_int($data) || is_string($data):
                // scalar value
                $data = $this->scalarToArrayUid($data);
                break;
            default:
                // not available values
                throw new Exception(vsprintf('Error %s line %d: value %s is not available!', [__METHOD__, __LINE__, Objects::toDebugString($mem_data)]));
                break;
        }
        if(!is_array($data)) {
            throw new Exception(vsprintf('Error %s line %d: value %s is not available to generate array of data!', [__METHOD__, __LINE__, Objects::toDebugString($mem_data)]));
        }
        return $data;
    }

    protected function getCompiledData(): array
    {
        $data = $this->data;
        // // 1 - Use Serialization mappings
        // $smap = $this->getSerializationMappings();
        // if(!empty($smap)) {
        //     foreach ($data as $field => $value) {
        //         if($realfieldmapping = $smap[$field] ?? false) {
        //             if(!$this->is_model || !static::MODELS_NO_ASSOCIATIONS) {
        //                 $interfaces = $realfieldmapping['require'];
        //                 /** @var AssociationMapping $mapping */
        //                 $mapping = $this->getClassMetadata()->getAssociationMapping($realfieldmapping['field']);
        //                 if($mapping->isToOne()) {
        //                     // ToOne entity - unique identifier (euid, uname, id)
        //                     // Try get Uname/Euid
        //                     if(is_scalar($value)) {
        //                         $targetEntity = $this->wireEm->getClassnameByEuidOrUname((string)$value);
        //                         $value = $this->scalarToArrayUid($value);
        //                     } else if(is_array($value)) {
        //                         if(count($value) > 1 && !$this->isProd()) {
        //                             throw new Exception(vsprintf('Error %s line %d: value %s must be a unique entity in toOne relation!', [__METHOD__, __LINE__, Objects::toDebugString($value)]));
        //                         }
                                
        //                         $identifier = $value['euid'] ?? $value['uname'] ?? $value['id'] ?? array_key_first($value);
        //                         if($identifier !== 0) {
        //                             $targetEntity = is_string($identifier)
        //                                 ? $this->wireEm->getClassnameByEuidOrUname($identifier)
        //                                 : $value['classname'];
        //                         }
        //                     }
        //                     if(!empty($mapping->targetEntity) && Objects::isAlmostOneOfIntefaces($mapping->targetEntity, $interfaces)) {
        //                         $data[$field] = new static($this->wireEm, $mapping->targetEntity, $this->scalarToArrayUid($value), $this->context, $this->main_group, $this->create_only, $this->is_model);
        //                     } else {
        //                         if(!$this->isProd()) {
        //                             throw new Exception(vsprintf('Error %s line %d: value %s must be almost one of instances: %s!', [__METHOD__, __LINE__, Objects::toDebugString($mapping->targetEntity), implode(', ', $interfaces)]));
        //                         }
        //                         unset($data[$field]);
        //                     }
        //                 } else if($mapping->isToMany()) {
        //                     // ToMany entities
        //                 }

        //                 if(is_array($value) && !array_is_list($value)) {
        //                     // One entity - list of named attributes
        //                     dd($value, $interfaces);
        //                     $targetEntity = $data['classname'] ?? null;
        //                     $targetEntity ??= $this->wireEm->getClassnameByEuidOrUname($value['euid'] ?? $value['uname'] ?? '');
        //                     if(!empty($targetEntity) && Objects::isAlmostOneOfIntefaces($targetEntity, $interfaces)) {
        //                         $data[$field] = new static($this->wireEm, $targetEntity, $value, $this->context, $this->main_group, $this->create_only, $this->is_model);
        //                     } else {
        //                         if(!$this->isProd()) {
        //                             throw new Exception(vsprintf('Error %s line %d: value %s must be almost one of instances: %s!', [__METHOD__, __LINE__, Objects::toDebugString($targetEntity), implode(', ', $interfaces)]));
        //                         }
        //                         unset($data[$field]);
        //                     }
        //                 } else if(is_scalar($value)) {
        //                     // One entity - unique identifier (euid, uname) / NOT ID!!!
        //                     $targetEntity = $this->wireEm->getClassnameByEuidOrUname((string)$value);
        //                     if(!empty($targetEntity) && Objects::isAlmostOneOfIntefaces($targetEntity, $interfaces)) {
        //                         $data[$field] = new static($this->wireEm, $targetEntity, $this->scalarToArrayUid($value), $this->context, $this->main_group, $this->create_only, $this->is_model);
        //                     } else {
        //                         if(!$this->isProd()) {
        //                             throw new Exception(vsprintf('Error %s line %d: value %s must be almost one of instances: %s!', [__METHOD__, __LINE__, Objects::toDebugString($targetEntity), implode(', ', $interfaces)]));
        //                         }
        //                         unset($data[$field]);
        //                     }
        //                 } else if(is_array($value)) {
        //                     // Many entities
        //                     $data[$field] = new ArrayCollection();
        //                     foreach ($value as $key => $val) {
        //                         $targetEntity = is_scalar($val) ? $this->wireEm->getClassnameByEuidOrUname((string)$val) : ($val['classname'] ?? null);
        //                         if(!empty($targetEntity) && Objects::isAlmostOneOfIntefaces($targetEntity, $interfaces)) {
        //                             $ndc = new static($this->wireEm, $targetEntity, $this->scalarToArrayUid($val), $this->context, $this->main_group, $this->create_only, $this->is_model);
        //                             $related = $this->wireEm->getNormaliserService()->denormalizeEntity($ndc, $ndc->getType());
        //                             if(!$data[$field]->contains($related)) {
        //                                 $data[$field]->add($related);
        //                             }
        //                         } else if(!$this->isProd()) {
        //                             throw new Exception(vsprintf('Error %s line %d: value %s must be almost one of instances: %s!', [__METHOD__, __LINE__, Objects::toDebugString($targetEntity), implode(', ', $interfaces)]));
        //                         }
        //                     }
        //                 }
        //             } else {
        //                 // If model, do not manage associations
        //                 unset($data[$field]);
        //             }
        //         }
        //     }
        // }
        foreach ($this->getAssociationMappings() as $field => $mapping) {
            /** @var AssociationMapping $mapp */
            $mapp = $mapping['mapping'];
            switch (true) {
                case $this->is_model && static::MODELS_NO_ASSOCIATIONS:
                    // If model, do not manage associations
                    if(isset($data[$field])) {
                        unset($data[$field]);
                    }
                    break;
                case $mapp->isToOne() && is_array($data[$field] ?? null):
                    $data[$field] = new static($this->wireEm, $mapp->targetEntity, $data[$field], $this->context, $this->main_group, $this->create_only, $this->is_model);
                    break;
                case $mapp->isToMany():
                    $targetEntity = $mapp->targetEntity;
                    foreach ($data[$field] as $key => $value) {
                        $related_data = $this->scalarToArrayUid($value);
                        if($related_data) {
                            $data[$field][$key] = new static($this->wireEm, $targetEntity, $related_data, $this->context, $this->main_group, $this->create_only, $this->is_model);
                        } else {
                            unset($data[$field][$key]);
                            throw new Exception(vsprintf('Error %s line %d: property "%s" value %s type is invalid!', [__METHOD__, __LINE__, $field, Objects::toDebugString($value)]));
                        }
                    }
                    break;
            }
        }
        return $data;
    }

    /**
     * Try find entity with data if exists
     */
    protected function findEntity(): bool
    {
        if(!$this->hasEntity() && $this->isCreateOrFind()) {
            $data = $this->getCompiledData();
            $entity = null;
            // if(!$this->isProd() && !$this->wireEm->isDebugMode()) {
            //     throw new Exception(vsprintf('Error %s line %d: debug mode (%d) must be enabled!', [__METHOD__, __LINE__, $this->wireEm->debug_mode]));
            // }
            $this->wireEm->incDebugMode(); // --> should be unnecessary
            // Try find entity if exists
            if (!empty($data['id'] ?? null)) {
                $repo = $this->wireEm->getRepository($this->getType());
                $entity = $repo->find($data['id']);
                if(empty($entity)) {
                    throw new Exception(vsprintf('Error %s line %d: entity with id "%s" should exist, but was not found!', [__METHOD__, __LINE__, $data['id']]));
                }
            }
            if (!$entity && Encoders::isEuidFormatValid($data['euid'] ?? null)) {
                $entity = $this->wireEm->findEntityByEuid($data['euid']);
                if(empty($entity)) {
                    throw new Exception(vsprintf('Error %s line %d: entity with euid "%s" should exist, but was not found!', [__METHOD__, __LINE__, $data['euid']]));
                }
            }
            if (!$entity && Encoders::isUnameFormatValid($this->uname)) {
                $entity = $this->wireEm->findEntityByUname($this->uname);
                if(empty($entity) && empty($this->data)) {
                    throw new Exception(vsprintf('Error %s line %d: entity with uname "%s" should exist, but was not found!', [__METHOD__, __LINE__, $this->uname]));
                }
            }
            $this->wireEm->decDebugMode(); // --> should be unnecessary
            if($entity instanceof WireEntityInterface) {
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
        $this->accessor ??= PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        foreach ($this->reverse_operations as $targetUname => $targetPropertys) {
            foreach ((array)$targetPropertys as $targetProperty) {
                $targetEntity = $this->wireEm->findEntityByUname($targetUname);
                if($targetEntity instanceof WireEntityInterface) {
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


    /***********************************************************************************************
     * CLASSMETADATA / MAPPINGS / CONTROLS
     **********************************************************************************************/

    protected function getAssociationMappings(): array
    {
        // $mappings = $this->getClassMetadata()->getAssociationMappings();

        // 1 - Regular associations
        $associations = $this->getClassMetadata()->getAssociationMappings();
        // 2 - Relative associations
        $mappings = $this->getSerializationMappings();
        // 3 - compile all associations
        foreach ($mappings as $field => $mapping) {
            $mappings[$field]['property'] = $field;
            $mappings[$field]['mapping'] = $associations[$mapping['field']];
        }
        foreach ($associations as $field => $mapping) {
            if($this->data[$field] ?? false) {
                $mappings[$field] ??= [
                    'property' => $mapping->fieldName,
                    'field' => $mapping->fieldName,
                    'mapping' => $mapping,
                    'require' => [$mapping->targetEntity],
                ];
            }
        }
        // dd($mappings, $associations);
        return $mappings;
        
        // return array_filter($mappings, fn(AssociationMapping $mapping) => !empty($this->data[$mapping->fieldName] ?? null));
    }

    protected function getSerializationMappings(): array
    {
        $method = true;
        if($method) {
            return defined($this->classname.'::ITEMS_ACCEPT') ? $this->classname::ITEMS_ACCEPT : [];
        }
        $mappings = Objects::getClassAttributes($this->getEntity() ?? $this->getType(), SerializationMapping::class);
        // Get first mapping
        /** @var SerializationMapping|false */
        $mapping = reset($mappings);
        return $mapping ? $mapping->getMapping() : [];
    }

    protected function getClassMetadata(): ?ClassMetadata
    {
        return $this->classMetadata ??= $this->wireEm->getClassMetadata($this->getType());
    }

    protected function setFieldValue(
        string $field,
        mixed $value
    ): static
    {
        $reflfield = $this->getClassMetadata()->reflFields[$field] ?? null;
        if($reflfield instanceof ReflectionProperty) {
            $this->getClassMetadata()->setFieldValue($this->entity, $reflfield->name, $value);
        } else {
            $this->accessor ??= PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
            $this->accessor->setValue($this->entity, $field, $value);
        }
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
        if(!is_a($this->classname, WireEntityInterface::class, true)) {
            $error_messages[] = vsprintf('Error %s line %d: class %s does not implement %s!', [__METHOD__, __LINE__, $this->classname, WireEntityInterface::class]);
        }
        if($this->hasEntity()) {
            // Has entity
            if(!is_a($this->entity, $this->classname)) {
                $error_messages[] = vsprintf('Error %s line %d: entity %s does not implement %s!', [__METHOD__, __LINE__, $this->entity->getClassname(), $this->classname]);
            }
            if($this->isCreateOnly() && !empty($this->entity->getId())) {
                $error_messages[] = vsprintf('Error %s line %d: %s is already persisted!', [__METHOD__, __LINE__, $this->entity]);
            }
        }
        if($exception && count($error_messages) > 0) {
            throw new Exception(vsprintf('Error messages in %s control:%s%s', [static::class, PHP_EOL.'- ', implode(PHP_EOL.'- ', $error_messages)]));
        }
        return $error_messages;
    }


}
