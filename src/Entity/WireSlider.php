<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\WireSliderInterface;


class WireSlider extends WireEcollection implements WireSliderInterface
{
    public const ICON = [
        'ux' => 'tabler:slideshow',
        'fa' => 'fa-sliders-h'
    ];
}