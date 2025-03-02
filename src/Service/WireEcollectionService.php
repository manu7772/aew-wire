<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\WireEcollection;
use Aequation\WireBundle\Service\interface\WireEcollectionServiceInterface;

abstract class WireEcollectionService extends WireItemService implements WireEcollectionServiceInterface
{

    public const ENTITY_CLASS = WireEcollection::class;

    /**
     * Check entity after any changes.
     *
     * @param WireEntityInterface $entity
     * @return void
     */
    public function checkEntity(
        WireEntityInterface $entity
    ): void
    {
        parent::checkEntity($entity);
        if($entity instanceof WireEcollectionInterface) {
            // Check here
        }
    }

}