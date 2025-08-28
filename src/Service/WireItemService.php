<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\PaginatedContextDataInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Component\PaginatedContextData;
use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\HydrationServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Service\interface\WireItemServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
// Symfony
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class WireItemService implements WireItemServiceInterface
{

    USE TraitBaseService;
    use TraitBaseEntityService;
    
    public const ENTITY_CLASS = WireItem::class;
    public const DEFAULT_CHECK_DB_OPTIONS = [
        'flush_one_by_one' => false,
        'load_all_if_less_or_equal_than' => 100, // If the number of entities is less or equal than this value, all entities will be loaded in one query
    ];

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEm,
        protected PaginatorInterface $paginator,
        protected HydrationServiceInterface $normalizer
    ) {
    }


    /****************************************************************************************************/
    /** PAGINABLE                                                                                       */
    /****************************************************************************************************/

    /**
     * Get paginated context data.
     *
     * @param Request $request
     * @return array
     */
    public function getPaginatedContextData(array $options = []): PaginatedContextDataInterface
    {
        $options = [
            'fields' => [
                'id' => [
                    'classes' => ['text-center','w-0'],
                    'sortable' => true,
                ],
                'name' => [
                    'sortable' => true,
                ],
            ],
        ];
        return new PaginatedContextData($this, $options);
    }

}