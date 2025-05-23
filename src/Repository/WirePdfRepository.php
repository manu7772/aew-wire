<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WirePdf;
use Aequation\WireBundle\Repository\interface\WirePdfRepositoryInterface;
use Aequation\WireBundle\Repository\WireItemRepository;

/**
 * @extends WireItemRepository
 */
abstract class WirePdfRepository extends WireItemRepository implements WirePdfRepositoryInterface
{

    const NAME = WirePdf::class;
    const ALIAS = 'wirepdf';

}
