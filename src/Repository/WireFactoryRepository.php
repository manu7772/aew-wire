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

    const NAME = WireFactory::class;
    const ALIAS = 'wire_factory';

}
