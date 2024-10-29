<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Repository\BaseWireRepository;
use Aequation\WireBundle\Repository\interface\WireItemRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends BaseWireRepository
 */
#[AsAlias(WireItemRepositoryInterface::class, public: true)]
class WireItemRepository extends BaseWireRepository implements WireItemRepositoryInterface
{

    const ENTITY_CLASS = WireItem::class;
    const NAME = 'w_item';

}
