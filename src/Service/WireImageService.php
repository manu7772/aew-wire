<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WireImage;
use Aequation\WireBundle\Service\interface\WireImageServiceInterface;

abstract class WireImageService extends WireItemService implements WireImageServiceInterface
{

    public const ENTITY_CLASS = WireImage::class;
    // public const ENTITY_TYPE = null;

}