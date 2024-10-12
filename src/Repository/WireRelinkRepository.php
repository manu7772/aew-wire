<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Repository\interface\WireRelinkRepositoryInterface;
use Aequation\WireBundle\Repository\WireItemRepository;

/**
 * @extends WireItemRepository
 */
abstract class WireRelinkRepository extends WireItemRepository implements WireRelinkRepositoryInterface
{

    // const ENTITY_CLASS = WireRelink::class;
    // const NAME = 'wire_Relink';

}
