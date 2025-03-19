<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WirePhonelinkInterface;
use Aequation\WireBundle\Entity\WirePhonelink;
use Aequation\WireBundle\Service\interface\WirePhonelinkServiceInterface;

class WirePhonelinkService extends WireRelinkService implements WirePhonelinkServiceInterface
{

    const ENTITY_CLASS = WirePhonelink::class;


}