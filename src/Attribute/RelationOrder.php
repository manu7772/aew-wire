<?php
namespace Aequation\WireBundle\Attribute;

use Aequation\WireBundle\Event\WireEntityEvent;
// PHP
use Attribute;
use Exception;

#[Attribute(Attribute::TARGET_PROPERTY)]
class RelationOrder extends BasePropertyAttribute
{

    public function apply(
        WireEntityEvent $event
    ): void
    {
        throw new Exception(vsprintf('Error %s line %d: not implemented yet!', [__METHOD__, __LINE__]));
    }


}
