<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireMenu;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Service\interface\WireMenuServiceInterface;

abstract class WireMenuService extends WireEcollectionService implements WireMenuServiceInterface
{

    public const ENTITY_CLASS = WireMenu::class;


}