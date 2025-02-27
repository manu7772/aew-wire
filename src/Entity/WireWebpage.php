<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\trait\Prefered;

class WireWebpage extends WireHtmlcode implements WireWebpageInterface
{
    use Prefered;

    public const ICON = [
        'ux' => 'tabler:brand-webflow',
        'fa' => 'fa-w'
    ];

}