<?php
namespace Aequation\WireBundle\Entity;

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
abstract class WireMenu extends WireEcollection implements WireMenuInterface
{

    use Prefered, Webpageable;

    public const ICON = [
        'ux' => 'tabler:list',
        'fa' => 'fa-bars'
    ];
    public const ITEMS_ACCEPT = [
        'items'         => [WireMenuInterface::class, WireWebpageInterface::class],
        'categorys'     => [WireCategoryInterface::class],
    ];

    // /**
    //  * @var Collection<int, WireCategoryInterface>
    //  */
    // #[ORM\ManyToMany(targetEntity: WireCategoryInterface::class)]
    // protected Collection $categorys;

    #[ORM\Column]
    protected bool $prefered = false;


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
        return $this->items->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()) && $item instanceof WireMenuInterface; });
    }

    // /**
    //  * @return Collection<int, WireCategoryInterface>
    //  */
    // public function getCategorys(): Collection
    // {
    //     return $this->categorys;
    // }

    // public function addCategory(WireCategoryInterface $category): static
    // {
    //     if($this->isAcceptsItemForEcollection($category, 'categorys') && is_a($this, $category->getType())) {
    //         if (!$this->categorys->contains($category)) {
    //             $this->categorys->add($category);
    //         }
    //     } else {
    //         $this->removeCategory($category);
    //         throw new Exception(vsprintf('Error %s line %d: category %s is not available for %s %s!', [__METHOD__, __LINE__, $category->__toString(), $this->getShortname(), $this->__toString()]));
    //     }
    //     return $this;
    // }

    // public function removeCategory(WireCategoryInterface $category): static
    // {
    //     $this->categorys->removeElement($category);
    //     return $this;
    // }

    // public function removeCategorys(): static
    // {
    //     foreach ($this->categorys as $category) {
    //         $this->removeCategory($category);
    //     }
    //     return $this;
    // }

}
