<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Repository\BaseWireRepository;
use Aequation\WireBundle\Repository\interface\WireItemRepositoryInterface;

/**
 * @extends BaseWireRepository
 */
abstract class WireItemRepository extends BaseWireRepository implements WireItemRepositoryInterface
{

    // const ENTITY_CLASS = WireItem::class;
    // const NAME = 'wire_item';

}
