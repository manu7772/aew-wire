<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireImage;
use Aequation\WireBundle\Repository\interface\WireImageRepositoryInterface;
use Aequation\WireBundle\Repository\WireItemRepository;

/**
 * @extends WireItemRepository
 */
abstract class WireImageRepository extends WireItemRepository implements WireImageRepositoryInterface
{

    // const ENTITY_CLASS = WireImage::class;
    // const NAME = 'wire_Image';

}
