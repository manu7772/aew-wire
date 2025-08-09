<?php
namespace Aequation\WireBundle\Component\interface;


interface WirePropertyVirtualAssociationMetadataInterface extends WirePropertyAssociationMetadataInterface
{
    public function getParent(): WirePropertyAssociationMetadataInterface;
}