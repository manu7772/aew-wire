<?php
namespace Aequation\WireBundle\Service;

use Exception;
use Aequation\WireBundle\Tools\Objects;
use Aequation\WireBundle\Entity\WireSlider;
use Aequation\WireBundle\Form\WireSliderType;
use Aequation\WireBundle\Component\PaginatedContextData;
use Aequation\WireBundle\Entity\interface\WireSliderInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
// PHP
use Aequation\WireBundle\Service\interface\WireSliderServiceInterface;
use Aequation\WireBundle\Service\interface\WireWebpageServiceInterface;
use Aequation\WireBundle\Component\interface\PaginatedContextDataInterface;

abstract class WireSliderService extends WireEcollectionService implements WireSliderServiceInterface
{

    public const ENTITY_CLASS = WireSlider::class;
    public const ENTITY_TYPE = WireSliderType::class;
    // public const WP_DEFAULT_UNAME = 'wp_page_slider'; // Uname of the default Webpage for this entity

    public function checkDatabase(OpresultInterface $opresult, bool $repair = false, array $options = []): void
    {
        parent::checkDatabase($opresult, $repair);
        $this->paginatedAction(
            callback: function ($entity) use ($opresult, $repair) {
                /** @var WireSliderInterface $entity */
                $this->entityCheckActions($entity, $opresult);
                return $repair;
            },
            options: array_merge(static::DEFAULT_CHECK_DB_OPTIONS, $options)
        );
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
                'items' => [
                    // 'classes' => ['w-1'],
                    // 'label' => 'Nb items',
                    'view_options' => [
                        'template' => ['from_string' => '{{ entity.items.count }}'],
                    ],
                    'sortable' => false,
                ],
            ],
        ];
        return new PaginatedContextData($this, $options);
    }

}