<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Dto\interface\WireEntityDtoInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Service\interface\HydrationServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
// PHP
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

class BaseDto implements WireEntityDtoInterface
{
    public const EXCEPTION_ON_RELATION_NOT_FOUND = false;

    public readonly string $_target;
    public readonly WireEntityServiceInterface $_service;
    public readonly HydrationServiceInterface $hydrator;
    public readonly WireClassMetadataManagerInterface $_wCmdm;
    public readonly WireClassMetadataInterface $_wCmd;
    public bool $_createRelations = true;
    public PropertyAccessorInterface $_accessor;
    public array $_options = [];
    // Extra data
    public ?array $_extra_data = null;
    // DEV
    // public array $_history = [];

    public function __construct(
        public $data,
        public readonly WireEntityManagerInterface $_wireEm,
        public array $_base_options = [],
    ) {
        $this->_accessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->getPropertyAccessor();
        $this->hydrator = $this->_wireEm->getHydrationService();
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
        $this->integrateData();
    }

    public function __toString(): string
    {
        return static::class;
    }

    public function toArray(): array
    {
        $values = array_filter(
            get_object_vars($this),
            fn (mixed $value, string $name): bool => preg_match('/^(?!_)/', $name) && (!empty($value) || is_numeric($value) || is_bool($value)),
            ARRAY_FILTER_USE_BOTH
        );
        return array_map(
            function (mixed $value): null|string|int|float|bool|array {
                switch (true) {
                    case $value instanceof DateTimeInterface:
                        return $value->format(DATE_ATOM);
                        break;
                    default:
                        return $value;
                        break;
                };
            },
            $values
        );
    }

    public function createRelations(bool $_createRelations): static
    {
        $this->_createRelations = $_createRelations;
        return $this;
    }

    public function isCreateRelations(): bool
    {
        return $this->_createRelations;
    }

    public function integrateData(): void
    {
        foreach ($this->data as $attr => $value) {
            $this->compileAttributeName($attr);
            if($this->_options[$attr]['enabled']) {
                switch (true) {
                    case preg_match('/^(?!_)/', $attr) && property_exists($this, $attr):
                        if(array_key_exists($attr, $this->_wCmd->fieldMappings)) {
                            /** @see https://www.doctrine-project.org/projects/doctrine-dbal/en/4.2/reference/types.html */
                            $type = $this->_wCmd->fieldMappings[$attr]['type'];
                            switch ($type) {
                                case 'string':
                                case 'text':
                                case 'ascii_string':
                                    if($this->_options[$attr]['replace'] || empty($this->{$attr}) || !is_string($this->{$attr})) {
                                        $this->{$attr} = (string) $value;
                                        // $this->addHistory($attr, $this->{$attr}, $this->_options[$attr]);
                                    }
                                    break;
                                case 'smallint':
                                case 'integer':
                                case 'bigint':
                                    if($this->_options[$attr]['replace'] || !is_int($this->{$attr})) {
                                        $this->{$attr} = (int) $value;
                                        // $this->addHistory($attr, $this->{$attr}, $this->_options[$attr]);
                                    }
                                    break;
                                case 'decimal':
                                case 'number':
                                case 'smallfloat':
                                case 'float':
                                    if($this->_options[$attr]['replace'] || !is_float($this->{$attr})) {
                                        $this->{$attr} = (float) $value;
                                        // $this->addHistory($attr, $this->{$attr}, $this->_options[$attr]);
                                    }
                                    break;
                                case 'date_immutable':
                                case 'datetimez_immutable':
                                case 'datetime_immutable':
                                case 'time_immutable':
                                    if($this->_options[$attr]['replace'] || !($this->{$attr} instanceof DateTimeImmutable)) {
                                        $this->{$attr} = new DateTimeImmutable($value);
                                        // $this->addHistory($attr, $this->{$attr}, $this->_options[$attr]);
                                    }
                                    break;
                                case 'date':
                                case 'datetimez':
                                case 'datetime':
                                case 'time':
                                    if($this->_options[$attr]['replace'] || !($this->{$attr} instanceof DateTime)) {
                                        $this->{$attr} = new DateTime($value);
                                        // $this->addHistory($attr, $this->{$attr}, $this->_options[$attr]);
                                    }
                                    break;
                                case 'dateinterval':
                                    if($this->_options[$attr]['replace'] || !($this->{$attr} instanceof DateInterval)) {
                                        $this->{$attr} = new DateInterval($value);
                                        // $this->addHistory($attr, $this->{$attr}, $this->_options[$attr]);
                                    }
                                    break;
                                case 'boolean':
                                    if($this->_options[$attr]['replace'] || !is_bool($this->{$attr})) {
                                        $this->{$attr} = (bool) $value;
                                        // $this->addHistory($attr, $this->{$attr}, $this->_options[$attr]);
                                    }
                                    break;
                                case 'json':
                                case 'jsonb':
                                case 'simple_array':
                                    if($this->_options[$attr]['replace'] || empty($this->{$attr}) || !is_array($this->{$attr})) {
                                        $this->{$attr} = (array) $value;
                                    } else {
                                        $this->{$attr} = array_unique(array_merge($this->{$attr}, (array) $value));
                                    }
                                    // $this->addHistory($attr, $this->{$attr}, $this->_options[$attr]);
                                    break;
                                // case 'binary':
                                // case 'blob':
                                //     throw new Exception(vsprintf('Error %s line %d: cannot set "%s" value for attribute "%s" in class %s.', [__METHOD__, __LINE__, $type, $attr, static::class]));
                                //     break;
                                default:
                                    throw new Exception(vsprintf('Error %s line %d: cannot set "%s" value for attribute "%s" in class %s.', [__METHOD__, __LINE__, $type, $attr, static::class]));
                                    // $this->{$attr} = $value;
                                    break;
                            }
                        } else if(array_key_exists($attr, $this->_wCmd->associationMappings)) {
                            /** @var AssociationMapping */
                            $relation = $this->_wCmd->associationMappings[$attr];
                            switch (true) {
                                case $attr === 'uname':
                                    if(!is_a($this->_target, TraitUnamedInterface::class, true)) {
                                        throw new Exception(vsprintf('Error %s line %d: class "%s" should implement "%s" to use the "uname" attribute.', [__METHOD__, __LINE__, static::class, TraitUnamedInterface::class]));
                                    }
                                    // if($this->_options[$attr]['replace'] || empty($this->{$attr})) {
                                        $this->{$attr} = $value;
                                        // $this->addHistory($attr, $this->{$attr}, $this->_options[$attr]);
                                    // }
                                    break;
                                case $relation->isToOne():
                                    if($this->_options[$attr]['replace'] || empty($this->{$attr})) {
                                        if($related = $this->tryFindEntity($value)) {
                                            $this->{$attr} = $related;
                                        } else if(static::EXCEPTION_ON_RELATION_NOT_FOUND) {
                                            if($this->isCreateRelations()) {
                                                throw new Exception(vsprintf('Error %s line %d: could not find related entity "%s" for attribute "%s" in class "%s". You should create it before.', [__METHOD__, __LINE__, $value, $attr, static::class]));
                                            }
                                            throw new Exception(vsprintf('Error %s line %d: could not find related entity "%s" for attribute "%s" in class "%s".', [__METHOD__, __LINE__, $value, $attr, static::class]));
                                        }
                                        if(!is_object($this->{$attr})) {
                                            $this->{$attr} = null;
                                        }
                                        // $this->addHistory($attr, $this->{$attr}, $this->_options[$attr]);
                                    }
                                    break;
                                case $relation->isToMany():
                                    $collection = new ArrayCollection();
                                    foreach ($value as $val) {
                                        if($related = $this->tryFindEntity($val)) {
                                            if(!$collection->contains($related)) {
                                                $collection->add($related);
                                            }
                                        } else if(static::EXCEPTION_ON_RELATION_NOT_FOUND) {
                                            if($this->isCreateRelations()) {
                                                throw new Exception(vsprintf('Error %s line %d: could not find related entity "%s" for attribute "%s" in class "%s". You should create it before.', [__METHOD__, __LINE__, $val, $attr, static::class]));
                                            }
                                            throw new Exception(vsprintf('Error %s line %d: could not find related entity "%s" for attribute "%s" in class "%s".', [__METHOD__, __LINE__, $val, $attr, static::class]));
                                        }
                                    }
                                    // if(!($this->{$attr} instanceof ArrayCollection)) {
                                    //     $this->{$attr} = new ArrayCollection();
                                    // }
                                    if($this->_options[$attr]['replace'] || $this->{$attr}->isEmpty()) {
                                        $this->{$attr} = $collection;
                                    } else {
                                        foreach ($collection as $item) {
                                            if(!$this->{$attr}->contains($item)) {
                                                $this->{$attr}->add($item);
                                            }
                                        }
                                    }
                                    // $this->addHistory($attr, $this->{$attr}, $this->_options[$attr]);
                                    break;
                                default:
                                    throw new Exception(vsprintf('Error %s line %d: unknown relation type "%s" for attribute "%s" in class "%s".', [__METHOD__, __LINE__, json_encode($relation->toArray()), $attr, static::class]));
                                    break;
                            }
                        } else {
                            // Try call
                            $this->_accessor->setValue(
                                $this,
                                $attr,
                                $value
                            );
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
        // dd($this, $this->toArray());
    }

    protected function tryFindEntity(int|string|array $value): ?object
    {
        if(is_array($value)) {
            $value = $value['id'] ?? $value['euid'] ?? $value['uname'] ?? null;
        }
        if(preg_match('/^\d+$/', (string) $value)) {
            $value = (int) $value; // Ensure value is an integer
        }
        if(!empty($value)) {
            if($found = $this->hydrator->tryFindCreated($value, null)) {
                return $found;
            }
            switch (true) {
                case is_string($value):
                    return $this->_wireEm->findByUniqueValue($value);
                    break;
                case is_int($value):
                    return $this->_wireEm->findById($this->_wCmd->name, $value);
                    break;
            }
        }
        return null;
    }

    protected function compileAttributeName(string &$name): void
    {
        $options = [
            'enabled' => isset($this->_base_options['enabled']) ? $this->_base_options['enabled'] : true,
            'replace' => isset($this->_base_options['replace']) ? $this->_base_options['replace'] : true,
        ];
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
            default:
                # code...
                break;
        }
        $this->_options[$name] = $options;
    }

    // protected function addHistory(
    //     string $attr,
    //     mixed $data,
    //     array $this->_options[$attr] = [],
    //     // ?string $message = null,
    // ): void
    // {
    //     $this->_history[] = [
    //         $attr => $data,
    //         '_options' => $this->_options[$attr],
    //         // '_message' => $message,
    //     ];
    // }

    public static function transformFromDto(mixed $value, mixed $source): mixed
    {
        return $value;
    }

    public static function transformToDto(mixed $value, mixed $source): mixed
    {
        return $source;
    }

}