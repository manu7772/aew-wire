<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Service\interface\WireFactoryServiceInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class WireFactoryService extends WireItemService implements WireFactoryServiceInterface
{

    public const ENTITY_CLASS = WireFactory::class;

    public function getPreferedFactory(): ?WireFactoryInterface
    {
        return $this->getRepository()->findOneBy(['prefered' => true]);
    }

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireFactoryInterface entities
        $this->wireEm->decDebugMode();
        return $opresult;
    }


    // /****************************************************************************************************/
    // /** PAGINABLE                                                                                       */
    // /****************************************************************************************************/

    // /**
    //  * Get paginated context data.
    //  *
    //  * @param Request $request
    //  * @return array
    //  */
    // public function getPaginatedContextData(
    //     ?Request $request = null
    // ): array
    // {
    //     $request ??= $this->appWire->getRequest();
    //     $fields =  [
    //         'id' => [
    //             'classes' => ['text-center','w-0'],
    //             'sortable' => true,
    //         ],
    //         'name' => [
    //             'view_options' => [
    //                 'template' => ['from_string' => '{{ entity.name }}{% if entity.firstname is not null %}<span class="pl-2 italic text-sm font-extralight opacity-75"> {{ entity.firstname }}</span>{% endif %}']
    //             ],
    //             'sortable' => true,
    //         ],
    //     ];
    //     $model = $this->createModel();
    //     $entities = $this->getPaginated();
    //     /** @var BaseWireRepository */
    //     $repo = $this->getRepository();
    //     return [
    //         'entities' => $entities,
    //         'fields' => $fields,
    //         'options' => [
    //             'alias' => $repo->getDefaultAlias(),
    //             'classname' => $model->getClassname(),
    //             'shortname' => $model->getShortname(),
    //             'trans_domain' => $model->getTrans_domain(),
    //             'actions' => true,
    //         ],
    //     ];
    // }

}