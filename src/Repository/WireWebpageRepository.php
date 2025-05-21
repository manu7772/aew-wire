<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireWebpage;
use Aequation\WireBundle\Repository\interface\WireWebpageRepositoryInterface;

/**
 * @extends WireWebpageRepository
 */
abstract class WireWebpageRepository extends WireItemRepository implements WireWebpageRepositoryInterface
{

    const NAME = WireWebpage::class;
    const ALIAS = 'wirewebpage';

}
