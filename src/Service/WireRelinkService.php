<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireRelinkServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
// Symfony
use Knp\Component\Pager\PaginatorInterface;

abstract class WireRelinkService implements WireRelinkServiceInterface
{

    USE TraitBaseService;
    use TraitBaseEntityService;
    
    public const ENTITY_CLASS = WireRelink::class;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService,
        protected PaginatorInterface $paginator,
        public readonly NormalizerServiceInterface $normalizer
    ) {
    }

    /**
     * Get entity classname
     *
     * @return string|null
     */
    public function getEntityClassname(): ?string
    {
        return (string)static::ENTITY_CLASS;
    }


}