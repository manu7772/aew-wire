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
    ) {}

    public function getMapping(): array
    {
        return $this->mapping;
    }

}