<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\WireSlideInterface;


class WireSlide extends WireItem implements WireSlideInterface
{
    public const ICON = [
        'ux' => 'tabler:photo-circle',
        'fa' => 'fa-image'
    ];
}