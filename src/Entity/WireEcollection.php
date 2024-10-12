<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\RelationOrder;
use Aequation\WireBundle\Entity\interface\TraitHasOrderedInterface;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Entity\trait\HasOrdered;
use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Repository\WireEcollectionRepository;
use Aequation\WireBundle\Service\interface\WireEcollectionServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;
// PHP
use Exception;

#[ORM\Entity(repositoryClass: WireEcollectionRepository::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ORM\HasLifecycleCallbacks]
#[ClassCustomService(WireEcollectionServiceInterface::class)]
class WireEcollection extends WireItem implements WireEcollectionInterface, TraitHasOrderedInterface
{
    use HasOrdered;

    #[ORM\ManyToMany(targetEntity: WireItem::class, mappedBy: 'parents', cascade: ['persist'])]
    #[RelationOrder()]
    #[Serializer\Ignore]
    protected Collection $items;

    public function __construct()
    {
        parent::__construct();
        $this->items = new ArrayCollection();
    }


    #[Serializer\Ignore]
    public function getItems(
        bool $filterActives = false
    ): Collection
    {
        return $this->items->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()); });
        // return $this->items;
    }

    #[Serializer\Ignore]
    public function getActiveItems(): Collection
    {
        return $this->items->filter(fn($item) => $item->isActive());
    }

    #[Serializer\Ignore]
    public function addItem(WireItem $item): bool
    {
        if($this->isAcceptsItemForEcollection($item, 'items')) {
            if (!$this->hasItem($item)) $this->items->add($item);
            if(!$item->hasParent($this)) $item->addParent($this);
        } else {
            // not acceptable
            $this->removeItem($item);
        }
        return $this->hasItem($item);
    }

    #[Serializer\Ignore]
    public function hasItem(WireEntityInterface $item): bool
    {
        return $this->items->contains($item);
    }

    public function removeItem(WireItem $item): static
    {
        $this->items->removeElement($item);
        if($item->hasParent($this)) $item->removeParent($this);
        return $this;
    }

    public function removeItems(): static
    {
        foreach ($this->items->toArray() as $item) {
            $this->removeItem($item);
        }
        return $this;
    }

    #[Serializer\Ignore]
    public function isAcceptsItemForEcollection(
        WireEntityInterface $item,
        string $property
    ): bool
    {   
        if($item !== $this) {
            foreach (static::ITEMS_ACCEPT[$property] as $class) {
                if(is_a($item, $class)) return true;
            }
        }
        return false;
    }

    #[Serializer\Ignore]
    public function filterAcceptedItemsForEcollection(
        Collection $items,
        string $property
    ): Collection
    {
        return $items->filter(fn($item) => $item !== $this && $this->isAcceptsItemForEcollection($item, $property));
    }

}
