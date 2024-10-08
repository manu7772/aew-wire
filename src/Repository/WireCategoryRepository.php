<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireCategory;
use Aequation\WireBundle\Repository\BaseWireRepository;
// Symfony
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends BaseWireRepository
 */
abstract class WireCategoryRepository extends BaseWireRepository
{

    const ENTITY_CLASS = WireCategory::class;
    const NAME = 'wire_category';


}
