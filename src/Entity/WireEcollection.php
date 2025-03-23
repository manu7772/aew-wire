<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\RelationOrder;
use Aequation\WireBundle\Entity\interface\ItemCollectionInterface;
use Aequation\WireBundle\Entity\interface\TraitHasOrderedInterface;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Entity\trait\HasOrdered;
use Aequation\WireBundle\Repository\WireEcollectionRepository;
use Aequation\WireBundle\Service\interface\WireEcollectionServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
// PHP
use Exception;

/**
 * Use Gedmo extension for sortable
 * @see https://github.com/doctrine-extensions/DoctrineExtensions/blob/main/doc/sortable.md
 */
#[ORM\Entity(repositoryClass: WireEcollectionRepository::class)]
#[ORM\Table(name: 'w_ecollection')]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ORM\HasLifecycleCallbacks]
#[ClassCustomService(WireEcollectionServiceInterface::class)]
abstract class WireEcollection extends WireItem implements WireEcollectionInterface
{

    public const ICON = [
        'ux' => 'tabler:folder',
        'fa' => 'fa-folder'
    ];
    public const ITEMS_ACCEPT = [
        'items' => [WireItemInterface::class],
    ];

    #[ORM\OneToMany(targetEntity: ItemCollectionInterface::class, mappedBy: 'ecollection', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $items;

    public function __construct()
    {
        parent::__construct();
        $this->items = new ArrayCollection();
    }

    // Position
    public function getItemPosition(WireItemInterface $item): int|false
    {
        foreach ($this->items as $ic) {
            if($ic->getItem() === $item) return $ic->getPosition();
        }
        return false;
    }

    public function setItemPosition(WireItemInterface $item, int $position): static
    {
        foreach ($this->items as $ic) {
            if($ic->getItem() === $item) {
                $ic->setPosition($position);
                return $this;
            }
        }
        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items->map(
            fn(ItemCollectionInterface $ic) => $ic->getItem()
        );
    }

    public function getActiveItems(): Collection
    {
        return $this->items
            ->map(fn(ItemCollectionInterface $ic) => $ic->getItem())
            ->filter(fn(WireItemInterface $item) => $item->isActive());
    }

    public function addItem(WireItemInterface $item): static
    {
        if($item !== $this && !$this->hasItem($item)) {
            $ic = new ItemCollection($this, $item);
            $this->items->add($ic);
        } else {
            $this->removeItem($item);
        }
        return $this;
    }

    public function hasItem(WireEntityInterface $item): bool
    {
        return $this->getItems()->contains($item);
    }

    public function removeItem(WireItemInterface $item): static
    {
        $this->items = $this->items->filter(
            fn(ItemCollectionInterface $ic) => $ic->getItem() !== $item
        );
        return $this;
    }

    public function removeItems(): static
    {
        $this->items->clear();
        return $this;
    }

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

    public function filterAcceptedItemsForEcollection(
        Collection $items,
        string $property
    ): Collection
    {
        return $items->filter(fn($item) => $item !== $this && $this->isAcceptsItemForEcollection($item, $property));
    }

}
