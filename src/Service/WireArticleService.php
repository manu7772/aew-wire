<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireArticleInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\WireArticle;
use Aequation\WireBundle\Service\interface\WireArticleServiceInterface;

abstract class WireArticleService extends WireItemService implements WireArticleServiceInterface
{

    public const ENTITY_CLASS = WireArticle::class;

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
        if($entity instanceof WireArticleInterface) {
            // Check here
        }
    }

}