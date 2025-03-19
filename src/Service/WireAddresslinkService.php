<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireAddresslinkInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\WireAddresslink;
use Aequation\WireBundle\Service\interface\WireAddresslinkServiceInterface;

class WireAddresslinkService extends WireRelinkService implements WireAddresslinkServiceInterface
{

    const ENTITY_CLASS = WireAddresslink::class;


}