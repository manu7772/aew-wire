<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireEcollection;
use Aequation\WireBundle\Repository\BaseWireRepository;
// Symfony
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends BaseWireRepository
 */
abstract class WireEcollectionRepository extends BaseWireRepository
{

    const ENTITY_CLASS = WireEcollection::class;
    const NAME = 'wire_category';


}
