<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireUrlinkInterface;
use Aequation\WireBundle\Entity\WireUrlink;
use Aequation\WireBundle\Service\interface\WireUrlinkServiceInterface;

class WireUrlinkService extends WireRelinkService implements WireUrlinkServiceInterface
{

    const ENTITY_CLASS = WireUrlink::class;


}