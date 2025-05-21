<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireCategory;
use Aequation\WireBundle\Repository\BaseWireRepository;
use Aequation\WireBundle\Repository\interface\WireCategoryRepositoryInterface;

/**
 * @extends BaseWireRepository
 */
abstract class WireCategoryRepository extends BaseWireRepository implements WireCategoryRepositoryInterface
{

    // const NAME = WireCategory::class;
    // const ALIAS = 'wire_category';

}
