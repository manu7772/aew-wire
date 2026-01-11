<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\WireSlideInterface;


class WireSlide extends WireImage implements WireSlideInterface
{
    public const ICON = [
        'ux' => 'tabler:slide',
        'fa' => 'fa-image'
    ];
}