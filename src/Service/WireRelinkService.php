<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireRelinkServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
// Symfony
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WireRelinkServiceInterface::class, public: true)]
class WireRelinkService implements WireRelinkServiceInterface
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


}