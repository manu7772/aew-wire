<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitDatetimedInterface;
use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

trait BaseRelinkCollection
{

    // Define Relation in the entity class:
    // #[ORM\Id]
    // #[ORM\ManyToOne(targetEntity: XxxxxxxxInterface::class, inversedBy: 'relinks')]
    protected $parent;

    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: WireRelinkInterface::class, cascade: ['persist'])]
    #[Assert\NotNull(groups: ['persist','update'])]
    #[Assert\Valid(groups: ['persist','update'])]
    protected WireRelinkInterface $relink;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Assert\NotNull(groups: ['persist','update'])]
    #[Gedmo\SortableGroup]
    protected string $sortgroup;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[Gedmo\SortablePosition]
    protected int $position = 0;

    public function __construct_baserelinkcollection(
        TraitRelinkableInterface $parent,
        WireRelinkInterface $relink
    ) {
        if($parent === $relink) throw new Exception(vsprintf('Error %s line %d: the parent and child parameters must be different', [__METHOD__, __LINE__]));
        $this->parent = $parent;
        $this->relink = $relink;
        $this->relink->setOwnereuid($this->parent);
        $this->synchParentLanguage();
        $this->updateSortgroup();
    }

    #[ORM\PrePersist]
    public function synchParentLanguage(): static
    {
        if($this->parent instanceof TraitDatetimedInterface && $this->parent->getLanguage()) {
            $this->relink->setLanguage($this->parent->getLanguage());
        }
        return $this;
    }

    public function getParent(): TraitRelinkableInterface
    {
        return $this->parent;
    }

    public function getRelink(): WireRelinkInterface
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