<?php

namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\NormalizerService;
use Aequation\WireBundle\Tools\Encoders;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
// PHP
use Exception;
use ReflectionProperty;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class NormalizeDataContainer
{
    public readonly WireEntityInterface $entity;
    public readonly ClassMetadata $classMetadata;
    public readonly string $classname;
    private string $main_group;
    private readonly bool $create_only;
    private PropertyAccessorInterface $accessor;
    private array $data;
    private ?string $uname = null;

    public function __construct(
        private readonly WireEntityManagerInterface $wireEm,
        string|WireEntityInterface $classOrEntity,
        array $data,
        private array $context = [],
        ?string $main_group = null,
        bool $create_only = true,
        private readonly bool $is_model = false,
    ) {
        $this->setMainGroup((string)$main_group);
        if($classOrEntity instanceof WireEntityInterface) $this->entity = $classOrEntity;
        $this->classname = is_string($classOrEntity) ? $classOrEntity : $classOrEntity->getClassname();
        $this->setData($data);
        // if($this->isCreateOrFind() && !$this->isModel()) {
        //     $this->findEntity();
        // }
        $this->defineCreateOnly($create_only);
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

    public function setEntity(
        WireEntityInterface $entity
    ): static
    {
        if($this->hasEntity() && $this->entity !== $entity) {
            throw new Exception(vsprintf('Error %s line %d: entity %s %s (id: %s) is already set!', [__METHOD__, __LINE__, $this->entity->getClassname(), $this->entity, $this->entity->getId() ?? 'NULL']));
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
        return $this;
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
        // Object to populate
        if($this->findEntity()) {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $this->getEntity();
        } else {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $this->wireEm->createEntity($this->getType());
        }
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
        if(!preg_match('/^[a-z0-9_]+$/', $main_group)) {
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
    
    private function defineCreateOnly(
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
        $this->data = [];
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'uname':
                    $this->uname = $value;
                    break;
                default:
                    $this->data[$key] = $value;
                    break;
            }
        }
        return $this;
    }

    private function getCompiledData(): array
    {
        $data = $this->data;
        // Compile here...
        // if (isset($data['unameName'])) {
        //     $data['uname'] ??= $data['unameName'];
        //     unset($data['unameName']);
        // }
        return $data;
    }

    /**
     * Try find entity with data if exists
     */
    public function findEntity(): bool
    {
        if(!$this->hasEntity() && $this->isCreateOrFind()) {
            $data = $this->getCompiledData();
            $entity = null;
            // Try find entity if exists
            if (!empty($data['id'] ?? null)) {
                $repo = $this->wireEm->getRepository($this->getType());
                $entity = $repo->find($data['id']);
            }
            if (!$entity && Encoders::isEuidFormatValid($data['euid'] ?? null)) {
                $entity = $this->wireEm->findEntityByEuid($data['euid']);
            }
            if (!$entity && Encoders::isUnameFormatValid($this->uname)) {
                $entity = $this->wireEm->findEntityByUname($this->uname);
            }
            if($entity instanceof WireEntityInterface) {
                $this->setEntity($entity);
            }
        }
        return $this->hasEntity();
    }


    /***********************************************************************************************
     * CLASSMETADATA / CONTROLS
     **********************************************************************************************/

    public function getAssociationMappings(): array
    {
        $data = $this->getCompiledData();
        $mappings = $this->getClassMetadata()->getAssociationMappings();
        return array_filter($mappings, fn(AssociationMapping $mapping) => !empty($data[$mapping->fieldName] ?? null));
    }

    public function getClassMetadata(): ?ClassMetadata
    {
        return $this->classMetadata ??= $this->wireEm->getClassMetadata($this->getType());
    }

    public function setFieldValue(
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
    private function controlContainer(
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
        if(is_string($this->classname) && !class_exists($this->classname)) {
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
                $error_messages[] = vsprintf('Error %s line %d: entity %s %s (id: %s) is already persisted!', [__METHOD__, __LINE__, $this->entity->getClassname(), $this->entity, $this->entity->getId() ?? 'NULL']);
            }
        }
        if($exception && count($error_messages) > 0) {
            throw new Exception(vsprintf('Error messages in %s control:%s%s', [static::class, PHP_EOL.'- ', implode(PHP_EOL.'- ', $error_messages)]));
        }
        return $error_messages;
    }


}
