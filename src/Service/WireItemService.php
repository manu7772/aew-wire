<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
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

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEm,
        protected PaginatorInterface $paginator,
        protected HydrationServiceInterface $normalizer
    ) {
    }

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $opresult ??= new Opresult();
        // Check all WireItemInterface entities
        return $opresult;
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
    public function getPaginatedContextData(
        ?Request $request = null
    ): array
    {
        $request ??= $this->appWire->getRequest();
        $fields =  [
            'id' => [
                'classes' => ['text-center','w-0'],
                'sortable' => true,
            ],
            'name' => [
                'sortable' => true,
            ],
        ];
        $model = $this->createModel();
        $entities = $this->getPaginated();
        /** @var BaseWireRepository */
        $repo = $this->getRepository();
        return [
            'entities' => $entities,
            'fields' => $fields,
            'options' => [
                'alias' => $repo->getDefaultAlias(),
                'classname' => $model->getClassname(),
                'shortname' => $model->getShortname(),
                'trans_domain' => $model->getTrans_domain(),
                'actions' => true,
            ],
        ];
    }

}