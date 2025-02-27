<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireUrlink;
use Aequation\WireBundle\Service\interface\WireUrlinkServiceInterface;

class WireUrlinkService extends WireRelinkService implements WireUrlinkServiceInterface
{

    const ENTITY_CLASS = WireUrlink::class;

}