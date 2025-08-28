<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\PaginatedContextDataInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPaginationInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

class PaginatedContextData implements PaginatedContextDataInterface
{

    public readonly string $classname;
    public readonly string $shortname;
    public readonly string $trans_domain;
    public readonly BaseEntityInterface $model;
    public readonly PaginationInterface $entities;
    public readonly string $alias;
    public array $fields;
    public bool $actions = true;
    public bool $top_buttons = true;
    public bool $turbo_frame = true;

    public function __construct(
        public readonly WireEntityServiceInterface $service,
        array $options = []
    )
    {
        $this->classname = $this->service->getEntityClassname();
        $this->shortname = $this->service->getEntityShortname();
        foreach ($options as $name => $values) {
            switch ($name) {
                case 'fields':
                    $this->fields = $values;
                    break;
                case 'alias':
                    $this->alias = $values;
                    break;
                case 'trans_domain':
                    $this->trans_domain = $values;
                    break;
                case 'actions':
                    $this->actions = $values;
                    break;
                case 'top_buttons':
                    $this->top_buttons = $values;
                    break;
                case 'turbo_frame':
                    $this->turbo_frame = $values;
                    break;
                default:
                    # code...
                    break;
            }
        }
        $this->model = $this->service->createModel();
        $this->trans_domain ??= $this->model->getTrans_domain();
        $this->entities = $this->service->getPaginated();
        $this->alias ??= $this->service->getRepository()->getDefaultAlias();
        $this->fields ??= $this->getDefaultFields();
    }

    public static function getDefaultFields(): array
    {
        $fields =  [
            'id' => [
                'classes' => ['w-1'],
                'sortable' => true,
            ],
            // 'name' => [
            //     'view_options' => [
            //         'template' => ['from_string' => '{{ entity.name }}{% if entity.firstname is not null %}<span class="pl-2 italic text-sm font-extralight opacity-75"> {{ entity.firstname }}</span>{% endif %}']
            //     ],
            //     'sortable' => true,
            // ],
        ];
        return $fields;
    }

}