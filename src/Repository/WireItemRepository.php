<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Repository\BaseWireRepository;
// Symfony
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends BaseWireRepository
 */
abstract class WireItemRepository extends BaseWireRepository
{

    const ENTITY_CLASS = WireItem::class;
    const NAME = 'wire_item';


}
