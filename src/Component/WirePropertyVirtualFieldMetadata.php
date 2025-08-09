<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WirePropertyFieldMetadataInterface;
use Aequation\WireBundle\Component\interface\WirePropertyVirtualFieldMetadataInterface;
// PHP
use InvalidArgumentException;

class WirePropertyVirtualFieldMetadata extends WirePropertyFieldMetadata implements WirePropertyVirtualFieldMetadataInterface
{
    public function __construct(
        public readonly WirePropertyFieldMetadataInterface $parent,
        public readonly WireClassMetadataInterface $wCmd,
        public array $parts = [],
    ) {
        if($this->parent instanceof self) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: parent cannot be an instance of %s', [__METHOD__, __LINE__, self::class]));
        }
        parent::__construct($parent, $wCmd, $parts);
    }

    public function getParent(): WirePropertyFieldMetadataInterface
    {
        return $this->parent;
    }



}