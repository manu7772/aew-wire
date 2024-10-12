<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireMenu;
use Aequation\WireBundle\Repository\interface\WireMenuRepositoryInterface;
use Aequation\WireBundle\Repository\WireItemRepository;

/**
 * @extends WireItemRepository
 */
abstract class WireMenuRepository extends WireEcollectionRepository implements WireMenuRepositoryInterface
{

    // const ENTITY_CLASS = WireMenu::class;
    // const NAME = 'wire_Menu';

}
