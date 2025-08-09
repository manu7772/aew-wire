<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\BetweenSortedChildInterface;
use Aequation\WireBundle\Entity\interface\WireBaseRelinkCollectionInterface;
use Aequation\WireBundle\Entity\interface\BetweenSortedParentInterface;
use Aequation\WireBundle\Entity\interface\TraitDatetimedInterface;
use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Sortable\Entity\Repository\SortableRepository;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: SortableRepository::class)]
#[ORM\Table(name: '`sorted_relinks_base`')]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('SINGLE_TABLE')]
abstract class WireBaseRelinkCollection implements WireBaseRelinkCollectionInterface
{
    protected TraitRelinkableInterface&BetweenSortedParentInterface $parent;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Assert\NotNull(groups: ['persist','update'])]
    #[Gedmo\SortableGroup]
    protected string $sortgroup;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[Gedmo\SortablePosition]
    protected int $position = 0;

    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: WireRelinkInterface::class, cascade: ['persist'], inversedBy: 'parent')]
    #[Assert\NotNull(groups: ['persist','update'])]
    #[Assert\Valid(groups: ['persist','update'])]
    protected WireRelinkInterface $relink;

    public function __construct(
        WireRelinkInterface&BetweenSortedChildInterface $relink
    ) {
        $this->relink = $relink;
        $this->synchParentLanguage();
        $this->updateSortgroup();
    }

    public function getChild(): object
    {
        return $this->getRelink();
    }

    #[ORM\PrePersist]
    public function synchParentLanguage(): static
    {
        if($this->parent instanceof TraitDatetimedInterface && $this->parent->getLanguage()) {
            $this->relink->setLanguage($this->parent->getLanguage());
        }
        return $this;
    }

    public function getParent(): TraitRelinkableInterface&BetweenSortedParentInterface
    {
        return $this->parent;
    }

    public function getRelink(): WireRelinkInterface&BetweenSortedChildInterface
    {
        return $this->relink;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function updateSortgroup(): static
    {
        $this->sortgroup = $this->parent->getEuid().'_'.$this->relink->getShortname();
        return $this;
    }

    public function getSortgroup(): string
    {
        return $this->sortgroup;
    }

    public function setSortgroup(string $sortgroup): static
    {
        $this->sortgroup = $sortgroup;
        return $this;
    }

}