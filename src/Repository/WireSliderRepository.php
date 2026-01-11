<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireSlider;
use Aequation\WireBundle\Repository\interface\WireSliderRepositoryInterface;
use Aequation\WireBundle\Repository\WireItemRepository;

/**
 * @extends WireItemRepository
 */
abstract class WireSliderRepository extends WireEcollectionRepository implements WireSliderRepositoryInterface
{
    const NAME = WireSlider::class;
    const ALIAS = 'wireslider';
}