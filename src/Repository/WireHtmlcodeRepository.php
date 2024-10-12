<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireHtmlcode;
use Aequation\WireBundle\Repository\interface\WireHtmlcodeRepositoryInterface;
use Aequation\WireBundle\Repository\WireItemRepository;

/**
 * @extends WireItemRepository
 */
abstract class WireHtmlcodeRepository extends WireEcollectionRepository implements WireHtmlcodeRepositoryInterface
{

    // const ENTITY_CLASS = WireHtmlcode::class;
    // const NAME = 'wire_htmlcode';

}