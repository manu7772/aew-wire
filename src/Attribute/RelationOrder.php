<?php
namespace Aequation\WireBundle\Attribute;

use Aequation\WireBundle\Event\WireEntityEvent;
// PHP
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class RelationOrder extends BasePropertyAttribute
{

    public function apply(
        WireEntityEvent $event
    ): void
    {
        
    }


}
