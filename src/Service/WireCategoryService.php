<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\PaginatedContextDataInterface;
use Aequation\WireBundle\Component\PaginatedContextData;
use Aequation\WireBundle\Entity\interface\TraitCategorizedInterface;
use Aequation\WireBundle\Entity\interface\WireCategoryInterface;
use Aequation\WireBundle\Entity\WireCategory;
use Aequation\WireBundle\Form\WireCategoryType;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireCategoryServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Knp\Component\Pager\PaginatorInterface;

abstract class WireCategoryService implements WireCategoryServiceInterface
{
    use TraitBaseService;
    use TraitBaseEntityService;
    
    public const ENTITY_CLASS = WireCategory::class;
    public const ENTITY_TYPE = WireCategoryType::class;

    public const DEFAULT_CHECK_DB_OPTIONS = [
        'flush_one_by_one' => false,
        'load_all_if_less_or_equal_than' => 1000, // If the number of entities is less or equal than this value, all entities will be loaded in one query
    ];

    public readonly array $availableTypes;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEm,
        protected PaginatorInterface $paginator,
    ) {
    }

    public function checkDatabase(OpresultInterface $opresult, bool $repair = false, array $options = []): void
    {
        $repaired = 0;
        $this->paginatedAction(
            callback: function ($entity) use ($opresult, $repair, &$repaired) {
                /** @var WireCategoryInterface $entity */
                $this->entityCheckActions($entity, $opresult);
                // Repair category type
                if(!class_exists($entity->getType())) {
                    $opresult->addWarning("Category type {$entity->getType()} does not exist");
                    if($repair) {
                        $entity->setType($entity->getType());
                        $repaired++;
                    }
                }
                return $repair;
            },
            options: array_merge(static::DEFAULT_CHECK_DB_OPTIONS, $options)
        );
        if($repaired > 0) {
            $this->getEntityManager()->flush();
            $opresult->addWarning("Repaired $repaired category type(s)");
        } else if($opresult->isSuccess()) {
            $opresult->addSuccess("All category types are valid");
        } else {
            $opresult->addWarning("Some category types are not valid");
        }
    }

    /**
     * Get available category types
     * - returns final entities related to WireCategoryInterface AND instance of TraitCategorizedInterface
     * 
     */
    public function getAvailableTypes(
        bool $asShornames = true
    ): array
    {
        if(!isset($this->availableTypes)) {
            $relateds = $this->wireEm->getEntitiesMetadata()->findFinals([TraitCategorizedInterface::class]);
            $availableTypes = [];
            foreach ($relateds as $wCmd) {
                $availableTypes[$wCmd->name] = $asShornames ? $wCmd->getShortname() : $wCmd->name;
            }
            $this->availableTypes = $availableTypes;
        }
        return $this->availableTypes;
    }

    /**
     * Get category type choices
     * 
     * @return array
     */
    public function getCategoryTypeChoices(): array
    {
        $choices = [];
        foreach ($this->getAvailableTypes(true) as $classname => $shortname) {
            $name = $this->appWire->get('translator')->trans('name', [], $shortname);
            if($name === 'name') {
                $name = $shortname;
            }
            $choices[ucfirst($name)] = $classname;
        }
        return $choices;
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
                    'classes' => ['w-1'],
                    'sortable' => true,
                ],
                'name' => [
                    'classes' => ['text-left'],
                    'sortable' => true,
                ],
                'typeShortname' => [
                    'classes' => ['text-left'],
                    'sortable' => true,
                ],
            ],
        ];
        return new PaginatedContextData($this, $options);
    }

}