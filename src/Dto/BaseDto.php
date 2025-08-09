<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Component\interface\TypedCollectionInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Dto\interface\WireEntityDtoInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WireEmbeddedInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Service\interface\HydrationServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\AssociationMapping;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
// PHP
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

abstract class BaseDto implements WireEntityDtoInterface
{
    public const EXCEPTION_ON_RELATION_NOT_FOUND = false;
    public const DEFAULT_OPTIONS = [
        'enabled' => true,
        'replace' => true,
        'export_empty_data' => false,
        'import_empty_data' => false,
        'create_relations' => true, // If true, relations will be created if not found
        'discard_missing_relations' => false, // If true, relations not found will be discarded
    ];

    #[Map(if: 'is_int')]
    public ?int $id = null;
    #[Map(if: 'strlen')]
    public ?string $classname = null;
    #[Map(if: 'strlen')]
    public ?string $shortname = null;
    #[Map(if: 'strlen')]
    public ?string $euid = null;

    public readonly string $_target;
    public readonly WireEntityServiceInterface $_service;
    public readonly HydrationServiceInterface $_hydrator;
    public readonly WireClassMetadataManagerInterface $_wCmdm;
    public readonly WireClassMetadataInterface $_wCmd;
    public PropertyAccessorInterface $_accessor;
    public array $_options = [];
    // Extra data
    public ?array $_extra_data = null;

    public function __construct(
        protected mixed $data,
        protected WireEntityManagerInterface $_wireEm,
        protected array $_base_options = [],
    ) {
        $this->initialize();
    }

    public function __toString(): string
    {
        return static::class;
    }

    public function toArray(): array
    {
        $values = array_filter(
            get_object_vars($this),
            fn (mixed $value, string $name): bool => preg_match('/^(?!_)/', $name) && !(empty($value) && !$this->getOption($name, 'export_empty_data') && !is_numeric($value) && !is_bool($value)),
            ARRAY_FILTER_USE_BOTH
        );
        return array_map(
            function (mixed $value): null|string|int|float|bool|array {
                switch (true) {
                    case $value instanceof DateTimeInterface:
                        return $value->format(DATE_ATOM);
                        break;
                    case $value instanceof TraitUnamedInterface:
                        return $value->getUnameName();
                        break;
                    case $value instanceof WireEntityInterface:
                        return $value->getEuid();
                        break;
                    case $value instanceof TypedCollectionInterface:
                        return $value->toArray();
                        break;
                    case ($value instanceof Collection):
                        return $value
                            ->map(fn (object $item): ?array => Objects::getObjectIdentifiers($item))
                            ->filter(fn (array $item): bool => !empty($item))
                            ->toArray()
                            ;
                        return $value;
                        break;
                    default:
                        return $value;
                        break;
                };
            },
            $values
        );
    }

    protected function initialize(): void
    {
        $this->_base_options = array_filter(
            array_merge(static::DEFAULT_OPTIONS, $this->_base_options),
            fn (int|string $key): bool => array_key_exists($key, static::DEFAULT_OPTIONS),
            ARRAY_FILTER_USE_KEY
        );
        $this->_accessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->getPropertyAccessor();
        $this->_hydrator = $this->_wireEm->getHydrationService();
        $this->_wCmdm = $this->_wireEm->getEntitiesMetadata();
        $maps = Objects::getDtoTargetClassnames(
            static::class,
            fn (Map $map): bool => is_a($map->target, WireHydratable::class, true)
        );
        $this->_target = reset($maps);
        $this->_service = $this->_wireEm->getEntityService($this->_target);
        $this->_wCmd = $this->_service->getWireClassMetadata();
        if($this->_wCmd->name !== $this->_target) {
            throw new Exception(sprintf('Error %s line %d: Dto target "%s" does not match service metadata name "%s"!', __METHOD__, __LINE__, $this->_target, $this->_wCmd->name));
        }
        $this->insertData($this->data);
    }

    public function insertData(array $data): void
    {
        $fieldMappings = [];
        foreach ($this->_wCmd->fieldMappings as $name => $map) {
            $parts = preg_split('/\./', $name);
            $name = reset($parts);
            $fieldMappings[$name] = $map;
        }
        foreach ($data as $attr => $value) {
            $this->compileAttributeName($attr);
            if(empty($value) && !$this->getOption($attr, 'import_empty_data') && !is_numeric($value) && !is_bool($value)) {
                continue;
            }
            if($this->_options[$attr]['enabled']) {
                switch (true) {
                    case preg_match('/^(?!_)/', $attr) && property_exists($this, $attr):
                        if(array_key_exists($attr, $fieldMappings)) {
                            if($this->convertFieldValue($fieldMappings[$attr], $value)) {
                                if(is_a($fieldMappings[$attr]['originalClass'], WireEmbeddedInterface::class, true)) {
                                    $class = $fieldMappings[$attr]['originalClass'];
                                    $this->{$attr} = new $class(...((array) $value));
                                } else {
                                    $this->{$attr} = $value;
                                }
                            }
                        } else if(array_key_exists($attr, $this->_wCmd->associationMappings)) {
                            /** @var AssociationMapping */
                            $relation = $this->_wCmd->associationMappings[$attr];
                            switch (true) {
                                case $attr === 'uname':
                                    if(is_a($this->_target, TraitUnamedInterface::class, true)) {
                                        $this->{$attr} = $value;
                                    }
                                    break;
                                case $relation->isToOne():
                                    if($this->_options[$attr]['replace'] || empty($this->{$attr})) {
                                        if($related = $this->tryFindEntity($value)) {
                                            $this->{$attr} = $related;
                                        } else if(!$this->getOption($attr, 'discard_missing_relations') && !$this->getOption($attr, 'create_relations')) {
                                            throw new Exception(vsprintf('Error %s line %d: could not find related entity "%s" for attribute "%s" in class "%s".', [__METHOD__, __LINE__, $value, $attr, static::class]));
                                        } else if($this->getOption($attr, 'create_relations')) {
                                            // Create a new entity if not found
                                            // ...
                                        }
                                        if(!is_object($this->{$attr})) {
                                            $this->{$attr} = null;
                                        }
                                    }
                                    break;
                                case $relation->isToMany():
                                    $collection = new ArrayCollection();
                                    foreach ($value as $val) {
                                        if($related = $this->tryFindEntity($val)) {
                                            if(!$collection->contains($related)) {
                                                $collection->add($related);
                                            }
                                        } else if(!$this->getOption($attr, 'discard_missing_relations') && !$this->getOption($attr, 'create_relations')) {
                                            throw new Exception(vsprintf('Error %s line %d: could not find related entity "%s" for attribute "%s" in class "%s".', [__METHOD__, __LINE__, $val, $attr, static::class]));
                                        } else if($this->getOption($attr, 'create_relations')) {
                                            // Create a new entity if not found
                                            // ...
                                        }
                                    }
                                    if($this->_options[$attr]['replace'] || $this->{$attr}->isEmpty()) {
                                        $this->{$attr} = $collection;
                                    } else {
                                        foreach ($collection as $item) {
                                            if(!$this->{$attr}->contains($item)) {
                                                $this->{$attr}->add($item);
                                            }
                                        }
                                    }
                                    break;
                                default:
                                    throw new Exception(vsprintf('Error %s line %d: unknown relation type "%s" for attribute "%s" in class "%s".', [__METHOD__, __LINE__, json_encode($relation->toArray()), $attr, static::class]));
                                    break;
                            }
                        } else {
                            // Try call accessor
                            $this->_accessor->setValue($this, $attr, $value);
                        }
                        break;
                    case $attr === '_extra_data':
                        // Add extra data actions
                        break;
                    default:
                        # code...
                        break;
                }
            }
        }
        // dump($this->_options);
    }

    /**
     *  @see https://www.doctrine-project.org/projects/doctrine-dbal/en/4.2/reference/types.html
     */
    protected function convertFieldValue(FieldMapping $map, mixed &$value): bool
    {
        $fieldName = $map->declaredField ?? $map->fieldName;
        switch ($map->type) {
            case 'string':
            case 'text':
            case 'ascii_string':
                if($this->_options[$fieldName]['replace'] || empty($this->{$fieldName}) || !is_string($this->{$fieldName})) {
                    $value = (string) $value;
                    return true;
                }
                break;
            case 'smallint':
            case 'integer':
            case 'bigint':
                if($this->_options[$fieldName]['replace'] || !is_int($this->{$fieldName})) {
                    $value = (int) $value;
                    return true;
                }
                break;
            case 'decimal':
            case 'number':
            case 'smallfloat':
            case 'float':
                if($this->_options[$fieldName]['replace'] || !is_float($this->{$fieldName})) {
                    $value = (float) $value;
                    return true;
                }
                break;
            case 'date_immutable':
            case 'datetimez_immutable':
            case 'datetime_immutable':
            case 'time_immutable':
                if($this->_options[$fieldName]['replace'] || !($this->{$fieldName} instanceof DateTimeImmutable)) {
                    $value = new DateTimeImmutable($value);
                    return true;
                }
                break;
            case 'date':
            case 'datetimez':
            case 'datetime':
            case 'time':
                if($this->_options[$fieldName]['replace'] || !($this->{$fieldName} instanceof DateTime)) {
                    $value = new DateTime($value);
                    return true;
                }
                break;
            case 'dateinterval':
                if($this->_options[$fieldName]['replace'] || !($this->{$fieldName} instanceof DateInterval)) {
                    $value = new DateInterval($value);
                    return true;
                }
                break;
            case 'boolean':
                if($this->_options[$fieldName]['replace'] || !is_bool($this->{$fieldName})) {
                    $value = (bool) $value;
                    return true;
                }
                break;
            case 'json':
            case 'jsonb':
            case 'simple_array':
                if($this->_options[$fieldName]['replace'] || empty($this->{$fieldName}) || !is_array($this->{$fieldName})) {
                    $value = (array) $value;
                } else {
                    $value = array_unique(array_merge($this->{$fieldName}, (array) $value));
                }
                return true;
                break;
            // case 'binary':
            // case 'blob':
            //     throw new Exception(vsprintf('Error %s line %d: cannot set "%s" value for attribute "%s" in class %s.', [__METHOD__, __LINE__, $map->type, $fieldName, static::class]));
            //     break;
            default:
                throw new Exception(vsprintf('Error %s line %d: cannot set "%s" value for attribute "%s" in class %s.', [__METHOD__, __LINE__, $map->type, $fieldName, static::class]));
                // $value = $value;
                break;
        }
        return false;
    }

    protected function tryFindEntity(int|string|array $value): ?object
    {
        $original_value = $value;
        if(is_array($value)) {
            $value = $value['id'] ?? $value['euid'] ?? $value['uname'] ?? $value['email'] ?? null;
        }
        if(preg_match('/^\d+$/', (string) $value)) {
            $value = (int) $value; // Ensure value is an integer
        }
        if(is_int($value) && !isset($original_value['classname'])) {
            throw new Exception(vsprintf('Error %s line %d: cannot find entity by ID %d without classname in original value.', [__METHOD__, __LINE__, $value]));
        }
        if(!empty($value)) {
            if($found = $this->_hydrator->tryFindCreated($value, $original_value['classname'] ?? null)) {
                return $found;
            }
            switch (true) {
                case is_string($value):
                    if($entity = $this->_wireEm->findByUniqueValue($value)) {
                        return $entity;
                    }
                    // if WireUser, try find by email
                    if(is_a($this->_wCmd->name, WireUserInterface::class, true) && ($user = $this->_wireEm->getRepository($this->_wCmd->name)->findOneOrNullByEmail($value))) {
                        return $user;
                    }
                    break;
                case is_int($value):
                    return $this->_wireEm->findById($original_value['classname'], $value);
                    break;
            }
        }
        return null;
    }

    protected function compileAttributeName(string &$name): void
    {
        $options = $this->_base_options;
        switch (true) {
            case preg_match('/^\~/', $name):
                /**
                 * if the name starts with a "~":
                 * - in iterable data, all elements of the data will be added to the array/collection instead of replacing the existing ones.
                 * - in scalar data, if the value is not empty, it will not be changed.
                 */
                $options['replace'] = false;
                $name = preg_replace('/^\~/', '', $name);
                break;
            case preg_match('/^\^/', $name):
                /**
                 * if the name starts with a "^":
                 * - if the element of relation is not found, no exception will be thrown, and the relation will stay empty.
                 */
                $options['discard_missing_relations'] = true;
                $name = preg_replace('/^\^/', '', $name);
                break;
            default:
                # code...
                break;
        }
        $this->_options[$name] = $options;
    }

    public function getOptions(string $attr): array
    {
        return $this->_options[$attr] ?? $this->_base_options;
    }

    public function getOption(string $attr, string $option): mixed
    {
        return $this->_options[$attr][$option] ?? $this->_base_options[$option];
    }


    /***********************************************************************************************************/
    /** CLASS-LEVEL CONDITIONS                                                                                 */
    /***********************************************************************************************************/

    /**
     * In class-level conditions:
     * - $value is not empty.
     * @param mixed $value
     * @param object $source (Dto object)
     * @return bool
     */
    public static function not_empty(mixed $value, object $source): bool
    {
        // dd($value, $source);
        return !empty($value);
    }
}