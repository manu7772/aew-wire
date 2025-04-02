<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\SerializationMapping;
use Aequation\WireBundle\Entity\interface\BetweenManyChildInterface;
use Aequation\WireBundle\Entity\interface\ItemCollectionInterface;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Repository\WireEcollectionRepository;
use Aequation\WireBundle\Service\interface\WireEcollectionServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Use Gedmo extension for sortable
 * @see https://github.com/doctrine-extensions/DoctrineExtensions/blob/main/doc/sortable.md
 */
#[ORM\Entity(repositoryClass: WireEcollectionRepository::class)]
#[ORM\Table(name: 'w_ecollection')]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ClassCustomService(WireEcollectionServiceInterface::class)]
#[ORM\HasLifecycleCallbacks]
#[SerializationMapping(WireEcollection::ITEMS_ACCEPT)]
abstract class WireEcollection extends WireItem implements WireEcollectionInterface
{

    public const ICON = [
        'ux' => 'tabler:folder',
        'fa' => 'fa-folder'
    ];
    public const ITEMS_ACCEPT = [
        'items' => [
            'field' => 'childs',
            'require' => [WireItemInterface::class],
        ],
    ];
    public const SORT_BETWEEN_MANY_BY_CHILDS_CLASS = false;

    #[ORM\OneToMany(targetEntity: ItemCollectionInterface::class, mappedBy: 'parent', cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Valid(groups: ['persist','update'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $childs;

    public function __construct()
    {
        parent::__construct();
        $this->childs = new ArrayCollection();
    }

    // Sortgroup
    public function getSortgroup(
        ?BetweenManyChildInterface $child = null
    ): string
    {
        return $this->getEuid().(static::SORT_BETWEEN_MANY_BY_CHILDS_CLASS && $child instanceof WireItemInterface ? '@'.$child->getShortname() : '');
    }

    // Position
    public function getItemPosition(WireItemInterface $item): int|false
    {
        foreach ($this->childs as $ic) {
            if($ic->getChild() === $item) return $ic->getPosition();
        }
        return false;
    }

    public function setItemPosition(WireItemInterface $item, int $position): static
    {
        foreach ($this->childs as $ic) {
            if($ic->getChild() === $item) {
                $ic->setPosition($position);
                return $this;
            }
        }
        return $this;
    }

    public function getItems(): Collection
    {
        return $this->childs->map(
            fn(ItemCollectionInterface $ic) => $ic->getChild($this)
        );
    }

    public function getActiveItems(): Collection
    {
        return $this->childs
            ->map(fn(ItemCollectionInterface $ic) => $ic->getChild())
            ->filter(fn(WireItemInterface $item) => $item->isActive());
    }

    public function setItems(iterable $items): static
    {
        $this->removeItems();
        foreach ($items as $item) {
            if($item instanceof WireItemInterface) $this->addItem($item);
        }
        return $this;
    }

    public function addItem(WireItemInterface $item): static
    {
        if($item !== $this && !$this->hasItem($item)) {
            $ic = new ItemCollection($this, $item);
            $this->childs->add($ic);
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
        // $this->childs = $this->childs->filter(
        //     fn(ItemCollectionInterface $ic) => $ic->getChild() !== $item
        // );
        foreach ($this->childs as $child) {
            if($child->getChild() === $item) {
                $this->childs->removeElement($child);
                $child->preRemove();
                break;
            }
        }
        return $this;
    }

    public function removeItems(): static
    {
        foreach ($this->childs as $child) {
            $this->removeItem($child->getChild());
        }
        return $this;
    }

    public function isAcceptsChildForParent(
        WireEntityInterface $item,
        string $property
    ): bool
    {   
        if($item !== $this) {
            foreach (static::ITEMS_ACCEPT[$property] as $field => $classes) {
                foreach ($classes as $class) {
                    if(is_a($item, $class)) return true;
                }
                if(is_a($item, $class)) return true;
            }
        }
        return false;
    }

    public function filterAcceptedChildsForParent(
        Collection $items,
        string $property
    ): Collection
    {
        return $items->filter(fn($item) => $item !== $this && $this->isAcceptsChildForParent($item, $property));
    }

}
