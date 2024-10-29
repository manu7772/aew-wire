<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireEcollection;
use Aequation\WireBundle\Repository\interface\WireEcollectionRepositoryInterface;
use Aequation\WireBundle\Repository\WireItemRepository;

/**
 * @extends WireItemRepository
 */
class WireEcollectionRepository extends WireItemRepository implements WireEcollectionRepositoryInterface
{

    const ENTITY_CLASS = WireEcollection::class;
    const NAME = 'w_ecollection';

}
