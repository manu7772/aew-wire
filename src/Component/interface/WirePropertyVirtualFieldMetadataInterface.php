<?php
namespace Aequation\WireBundle\Component\interface;


interface WirePropertyVirtualFieldMetadataInterface extends WirePropertyFieldMetadataInterface, WirePropertyVirtualMetadataInterface
{
    public function getParent(): WirePropertyFieldMetadataInterface;
}