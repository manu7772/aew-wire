<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
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
    public const DEFAULT_CHECK_DB_OPTIONS = [
        'flush_one_by_one' => false,
        'load_all_if_less_or_equal_than' => 1000, // If the number of entities is less or equal than this value, all entities will be loaded in one query
    ];

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEm,
        protected PaginatorInterface $paginator
    ) {
    }


}