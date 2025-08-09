<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\WireRelationMapping;
use Aequation\WireBundle\Entity\trait\BetweenSortedParent;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Repository\WireEcollectionRepository;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\BetweenSortedChildInterface;
use Aequation\WireBundle\Entity\interface\WireItemCollectionInterface;
use Aequation\WireBundle\Service\interface\WireEcollectionServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
// PHP
use Traversable;
use ArrayIterator;

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
#[WireRelationMapping(WireEcollection::ITEMS_ACCEPT)]
abstract class WireEcollection extends WireItem implements WireEcollectionInterface
{

    use BetweenSortedParent;

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

    #[ORM\OneToMany(targetEntity: WireItemCollectionInterface::class, mappedBy: 'parent', cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Valid(groups: ['persist','update'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $childs;
    protected Collection $temp_childs;

    public function __construct()
    {
        parent::__construct();
        $this->childs = new ArrayCollection();
    }

    // Has childs
    public function isEmpty(): bool
    {
        return $this->childs->isEmpty();
    }

    public function hasChilds(): bool
    {
        return !$this->isEmpty();
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->childs);
    }

    public function count(): int
    {
        return $this->childs->count();
    }

    #[ORM\PostLoad]
    public function initTempChilds(): void
    {
        $this->temp_childs = new ArrayCollection($this->childs->toArray());
    }
    
    public function findTempChild(
        WireItemInterface|WireItemCollectionInterface $child
    ): ?WireItemCollectionInterface
    {
        if(!$child->getSelfState()->isNew() && !$this->getSelfState()->isNew()) {
            foreach ($this->temp_childs as $temp_child) {
                /** @var WireItemCollectionInterface $temp_child */
                if($temp_child->getChild(false) === $child || $temp_child === $child) {
                    return $temp_child;
                }
            }
        }
        return null;
    }

    public function getChilds(): Collection
    {
        return $this->childs;
    }

    public function setChilds(iterable $childs): static
    {
        $this->removeChilds();
        foreach ($childs as $child) {
            $this->addChild($child);
        }
        return $this;
    }
    
    public function addChild(WireItemInterface|WireItemCollectionInterface $child): static
    {
        if(!$this->hasChild($child)) {
            $new_child = $this->findTempChild($child);
            $new_child ??= $child instanceof WireItemCollectionInterface ? $child : new WireItemCollection($this, $child);
            if(!$this->childs->contains($new_child)) {
                $this->childs->add($new_child);
            }
        }
        return $this;
    }

    public function hasChild(WireItemInterface|WireItemCollectionInterface $child): bool
    {
        foreach ($this->childs as $ic) {
            /** @var WireItemCollectionInterface $ic */
            // if($child instanceof WireItemInterface) {
            //     if($ic->getChild(false) === $child) {
            //         return true;
            //     }
            // } else {
            //     if($ic === $child || $ic->getChild(false) === $child->getChild(false)) {
            //         return true;
            //     }
            // }
            if($ic->getChild(false) === $child || $ic === $child || ($child instanceof WireItemCollectionInterface && $ic->getChild(false) === $child->getChild(false))) {
                return true;
            }
        }
        return false;
    }

    public function removeChild(WireItemInterface|WireItemCollectionInterface $child): static
    {
        if($child instanceof WireItemInterface) {
            foreach ($this->childs as $ic) {
                /** @var WireItemCollectionInterface $ic */
                if($ic->getChild(false) === $child) {
                    return $this->removeChild($ic);
                }
            }
        }
        $this->childs->removeElement($child);
        return $this;
    }

    public function removeChilds(): static
    {
        foreach ($this->childs as $child) {
            $this->removeChild($child);
        }
        return $this;
    }

    // Position
    public function getItemPosition(WireItemInterface $item): int|false
    {
        foreach ($this->childs as $ic) {
            if($ic->getChild(false) === $item) return $ic->getPosition();
        }
        return false;
    }

    public function setItemPosition(WireItemInterface $item, int $position): static
    {
        foreach ($this->childs as $ic) {
            if($ic->getChild(false) === $item) {
                $ic->setPosition($position);
                break;
            }
        }
        return $this;
    }

    public function getItems(): Collection
    {
        return $this->childs->map(
            fn(WireItemCollectionInterface $ic) => $ic->getChild(true)
        );
    }

    public function getActiveItems(): Collection
    {
        return $this->childs
            ->map(fn(WireItemCollectionInterface $ic) => $ic->getChild(true))
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
        $this->addChild($item);
        return $this;
    }

    public function hasItem(WireItemInterface $item): bool
    {
        return $this->hasChild($item);
    }

    public function removeItem(WireItemInterface $item): static
    {
        return $this->removeChild($item);
    }

    public function removeItems(): static
    {
        return $this->removeChilds();
    }

    // Sortgroup
    public function getSortgroup(?BetweenSortedChildInterface $child = null): string
    {
        return $this->getEuid().(static::SORT_BETWEEN_MANY_BY_CHILDS_CLASS && $child instanceof WireItemInterface ? '@'.$child->getShortname() : '');
    }


}
