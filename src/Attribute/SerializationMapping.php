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
class SerializationMapping extends BaseClassAttribute implements AppAttributeClassInterface
{

    public function __construct(
        public array $mapping,
    ) {
        foreach ($this->mapping as $field => $mapping) {
            $this->mapping[$field]['field'] ??= $field;
            $this->mapping[$field]['require'] = (array)$this->mapping[$field]['require'];
        }
    }

    public function getMapping(): array
    {
        return $this->mapping;
    }

}