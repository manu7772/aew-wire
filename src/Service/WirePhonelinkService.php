<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WirePhonelink;
use Aequation\WireBundle\Form\WirePhonelinkType;
use Aequation\WireBundle\Service\interface\WirePhonelinkServiceInterface;

class WirePhonelinkService extends WireRelinkService implements WirePhonelinkServiceInterface
{

    const ENTITY_CLASS = WirePhonelink::class;
    const ENTITY_TYPE = WirePhonelinkType::class;

}