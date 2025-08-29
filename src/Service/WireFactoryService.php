<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\PaginatedContextDataInterface;
use Aequation\WireBundle\Component\PaginatedContextData;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Form\WireFactoryType;
use Aequation\WireBundle\Service\interface\WireFactoryServiceInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class WireFactoryService extends WireItemService implements WireFactoryServiceInterface
{

    public const ENTITY_CLASS = WireFactory::class;
    public const ENTITY_TYPE = WireFactoryType::class;

    public function getPreferedFactory(): ?WireFactoryInterface
    {
        return $this->getRepository()->findOneBy(['prefered' => true]);
    }



    // /****************************************************************************************************/
    // /** PAGINABLE                                                                                       */
    // /****************************************************************************************************/

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
                    'classes' => ['w-1'],
                    'sortable' => true,
                ],
                'name' => [
                    'classes' => ['text-left'],
                    'sortable' => true,
                ],
                'associates' => [
                    // 'classes' => ['w-1'],
                    // 'label' => 'Nb sections',
                    'view_options' => [
                        'template' => ['from_string' => '{{ entity.associates.count }}'],
                    ],
                    'sortable' => false,
                ],
            ],
        ];
        return new PaginatedContextData($this, $options);
    }

}