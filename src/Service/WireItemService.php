<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Service\interface\WireItemServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
// Symfony
use Knp\Component\Pager\PaginatorInterface;

abstract class WireItemService implements WireItemServiceInterface
{

    USE TraitBaseService;
    use TraitBaseEntityService;
    
    public const ENTITY_CLASS = WireItem::class;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService,
        protected PaginatorInterface $paginator,
        public readonly NormalizerServiceInterface $normalizer
    ) {
    }


}