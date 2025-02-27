<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Repository\interface\WireFactoryRepositoryInterface;
use Aequation\WireBundle\Repository\WireItemRepository;

/**
 * @extends WireItemRepository
 */
abstract class WireFactoryRepository extends WireItemRepository implements WireFactoryRepositoryInterface
{

    const ENTITY_CLASS = WireFactory::class;
    const NAME = 'wire_factory';

}
