<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireSlide;
use Aequation\WireBundle\Form\WireSlideType;
use Aequation\WireBundle\Service\interface\WireSlideServiceInterface;

abstract class WireSlideService extends WireImageService implements WireSlideServiceInterface
{

    public const ENTITY_CLASS = WireSlide::class;
    public const ENTITY_TYPE = WireSlideType::class;

}