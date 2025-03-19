<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\WireEcollection;
use Aequation\WireBundle\Service\interface\WireEcollectionServiceInterface;

abstract class WireEcollectionService extends WireItemService implements WireEcollectionServiceInterface
{

    public const ENTITY_CLASS = WireEcollection::class;


}