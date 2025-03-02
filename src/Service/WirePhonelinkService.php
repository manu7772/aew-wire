<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WirePhonelinkInterface;
use Aequation\WireBundle\Entity\WirePhonelink;
use Aequation\WireBundle\Service\interface\WirePhonelinkServiceInterface;

class WirePhonelinkService extends WireRelinkService implements WirePhonelinkServiceInterface
{

    const ENTITY_CLASS = WirePhonelink::class;

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
        if($entity instanceof WirePhonelinkInterface) {
            // Check here
        }
    }

}