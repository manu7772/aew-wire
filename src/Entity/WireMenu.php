<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\AdminGroup;
use Aequation\WireBundle\Attribute\WireRelationMapping;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\trait\Prefered;
use Aequation\WireBundle\Entity\trait\Webpageable;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity(fields: ['name'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
#[WireRelationMapping(WireMenu::ITEMS_ACCEPT)]
#[AdminGroup(group: 'WireWebpage', order: 5, icon: 'tabler:letter-w')]
abstract class WireMenu extends WireEcollection implements WireMenuInterface
{

    use Prefered, Webpageable;

    public const ICON = [
        'ux' => 'tabler:list',
        'fa' => 'fa-bars'
    ];
    public const ITEMS_ACCEPT = [
        'childs' => [
            'field' => 'childs',
            'require' => [WireWebpageInterface::class],
        ],
        'items' => [
            'field' => 'childs',
            'require' => [WireMenuInterface::class],
        ],
    ];

    public const MAX_PREFERED = 1; // 1 is the maximum number of prefered sections in the database
    public const MIN_PREFERED = 1; // 1 is the minimum number of prefered sections in the database
    public const BY_PREFERED = [];


    // public function __construct()
    // {
    //     parent::__construct();
    // }

    public function getMaxPrefered(): ?int
    {
        return static::MAX_PREFERED;
    }

    public function getMinPrefered(): ?int
    {
        return static::MIN_PREFERED;
    }

    public function getPreferedBy(): array
    {
        return static::BY_PREFERED;
    }

    public function getWebpages(bool $filterActives = false): Collection
    {
        // return $this->getItems($filterActives);
        return $this->getItems()->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()) && $item instanceof WireWebpageInterface; });
    }

    public function getSubmenus(bool $filterActives = false): Collection
    {
        return $this->getItems()->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()) && $item instanceof WireMenuInterface; });
    }

}
