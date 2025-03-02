<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireMenu;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Service\interface\WireMenuServiceInterface;

abstract class WireMenuService extends WireEcollectionService implements WireMenuServiceInterface
{

    public const ENTITY_CLASS = WireMenu::class;


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
        if($entity instanceof WireMenuInterface) {
            // Check here
        }
    }

}