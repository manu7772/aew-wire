<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
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
        protected WireEntityManagerInterface $wireEm,
        protected PaginatorInterface $paginator,
        protected NormalizerServiceInterface $normalizer
    ) {
    }

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult ??= new Opresult();
        // Check all WireItemInterface entities
        $this->wireEm->decDebugMode();
        return $opresult;
    }


}