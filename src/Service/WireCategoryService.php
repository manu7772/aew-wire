<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireCategoryInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\WireCategory;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireCategoryServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
// Symfony
use Knp\Component\Pager\PaginatorInterface;

abstract class WireCategoryService implements WireCategoryServiceInterface
{
    USE TraitBaseService;
    use TraitBaseEntityService;
    
    public const ENTITY_CLASS = WireCategoryInterface::class;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService,
        protected PaginatorInterface $paginator,
        public readonly NormalizerServiceInterface $normalizer
    ) {
    }


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
        $this->wireEntityService->checkEntityBase($entity);
        if($entity instanceof WireCategoryInterface) {
            // Check here
        }
    }

    public function getCategoryTypeChoices(): array
    {
        $choices = [];
        
        return $choices;
    }

}