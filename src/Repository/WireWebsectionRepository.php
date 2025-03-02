<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireWebsection;
use Aequation\WireBundle\Repository\interface\WireWebsectionRepositoryInterface;

/**
 * @extends WireWebsectionRepository
 */
abstract class WireWebsectionRepository extends WireEcollectionRepository implements WireWebsectionRepositoryInterface
{

    const ENTITY_CLASS = WireWebsection::class;
    const NAME = 'wirewebsection';

}
