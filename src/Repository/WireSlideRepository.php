<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireSlide;
use Aequation\WireBundle\Repository\interface\WireSlideRepositoryInterface;
use Aequation\WireBundle\Repository\WireItemRepository;

/**
 * @extends WireItemRepository
 */
abstract class WireSlideRepository extends WireImageRepository implements WireSlideRepositoryInterface
{
    const NAME = WireSlide::class;
    const ALIAS = 'wireslide';
}