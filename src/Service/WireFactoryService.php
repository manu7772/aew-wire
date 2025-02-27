<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Service\interface\WireFactoryServiceInterface;

abstract class WireFactoryService extends WireItemService implements WireFactoryServiceInterface
{

    public const ENTITY_CLASS = WireFactory::class;

}