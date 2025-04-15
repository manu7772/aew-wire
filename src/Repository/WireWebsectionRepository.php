<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireWebsection;
use Aequation\WireBundle\Repository\interface\WireWebsectionRepositoryInterface;

/**
 * @extends WireWebsectionRepository
 */
abstract class WireWebsectionRepository extends BaseWireRepository implements WireWebsectionRepositoryInterface
{

    const NAME = WireWebsection::class;
    const ALIAS = 'wirewebsection';

}
