<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\SerializationMapping;
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
#[SerializationMapping(WireMenu::ITEMS_ACCEPT)]
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


    public function __construct()
    {
        parent::__construct();
        // $this->categorys = new ArrayCollection();
    }

    public function getWebpages(
        bool $filterActives = false
    ): ArrayCollection
    {
        return $this->getItems($filterActives);
        // return $this->items->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()) && $item instanceof WebpageInterface; });
    }

    public function getSubmenus(
        bool $filterActives = false
    ): ArrayCollection
    {
        return $this->childs->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()) && $item instanceof WireMenuInterface; });
    }

}
