<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireEmailinkInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\WireEmailink;
use Aequation\WireBundle\Service\interface\WireEmailinkServiceInterface;

class WireEmailinkService extends WireRelinkService implements WireEmailinkServiceInterface
{

    const ENTITY_CLASS = WireEmailink::class;

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
        if($entity instanceof WireEmailinkInterface) {
            // Check here
        }
    }

}