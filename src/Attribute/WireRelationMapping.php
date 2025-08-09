<?php
namespace Aequation\WireBundle\Attribute;

use Aequation\WireBundle\Attribute\interface\AppAttributeClassInterface;
// PHP
use Attribute;

/**
 * Mapping for serialization
 * @Target({"CLASS"})
 * @author emmanuel:dujardin Aequation
 */
#[Attribute(Attribute::TARGET_CLASS)]
class WireRelationMapping extends BaseClassAttribute implements AppAttributeClassInterface
{
    public readonly array $mapping;

    public function __construct(
        array $mapping,
    ) {
        $this->compileMapping($mapping);
    }

    public function isValid(): bool
    {
        return !empty($this->mapping);
    }

    public function getMapping(): array
    {
        return $this->mapping;
    }

    protected function compileMapping(array $mapping): void
    {
        $grouped = [];
        foreach ($mapping as $prop => $mapping) {
            $field = $mapping['field'] ?? $prop;
            $grouped[$field]['properties'][$prop] = (array) $mapping['require'];
            $grouped[$field]['requires'] = array_unique(array_merge($grouped[$field]['requires'] ?? [], (array) $mapping['require']));
            $grouped[$field]['field'] = $mapping['field'];
        }
        $this->mapping = $grouped;
    }


    // Field level

    public function hasField(string $fieldname): bool
    {
        return array_key_exists($fieldname, $this->mapping);
    }

    public function getFieldPropertyNames(string $fieldname): array
    {
        return $this->hasField($fieldname) ? array_keys($this->mapping[$fieldname]['properties']) : [];
    }

    public function getFieldRequierements(string $fieldname): false|array
    {
        return $this->hasField($fieldname) ? $this->mapping[$fieldname]['requires'] : false;
    }

    // Virtual property level

    public function hasFieldProperty(string $fieldname, string $property): bool
    {
        return $this->hasField($fieldname) && array_key_exists($property, $this->mapping[$fieldname]['properties']);
    }

    public function getFieldProperties(string $fieldname): array
    {
        return $this->hasField($fieldname) ? $this->mapping[$fieldname]['properties'] : [];
    }

    public function getFieldPropertyRequirements(string $fieldname, string $property): false|array
    {
        return $this->hasFieldProperty($fieldname, $property) ? $this->mapping[$fieldname]['properties'][$property] : false;
    }

    // public function fieldHasProperty(string $fieldname, string $property): bool
    // {
    //     return isset($this->mapping[$fieldname]['properties'][$property]);
    // }

}