<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\WireRelationMapping;
use Aequation\WireBundle\Entity\interface\WireCategoryInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\trait\Prefered;
use Aequation\WireBundle\Entity\trait\Webpageable;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity(fields: ['name'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
#[WireRelationMapping(WireMenu::ITEMS_ACCEPT)]
abstract class WireMenu extends WireEcollection implements WireMenuInterface
{

    use Prefered, Webpageable;

    public const ICON = [
        'ux' => 'tabler:list',
        'fa' => 'fa-bars'
    ];
    public const ITEMS_ACCEPT = [
        'items' => [
            'field' => 'childs',
            'require' => [WireMenuInterface::class, WireWebpageInterface::class],
        ],
    ];


    // public function __construct()
    // {
    //     parent::__construct();
    // }

    public function getWebpages(
        bool $filterActives = false
    ): ArrayCollection
    {
        // return $this->getItems($filterActives);
        return $this->getItems()->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()) && $item instanceof WireWebpageInterface; });
    }

    public function getSubmenus(
        bool $filterActives = false
    ): ArrayCollection
    {
        return $this->getItems()->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()) && $item instanceof WireMenuInterface; });
    }

}
