<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Entity\WireImage;
use Aequation\WireBundle\Service\interface\WireImageServiceInterface;

abstract class WireImageService extends WireItemService implements WireImageServiceInterface
{

    public const ENTITY_CLASS = WireImage::class;

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
        if($entity instanceof WireImageInterface) {
            // Check here
        }
    }

}