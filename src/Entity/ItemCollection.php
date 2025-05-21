<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\BetweenManyChildInterface;
use Aequation\WireBundle\Entity\interface\BetweenManyParentInterface;
use Aequation\WireBundle\Entity\interface\ItemCollectionInterface;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Tools\Encoders;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sortable\Entity\Repository\SortableRepository;
// PHP
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @see https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/association-mapping.html#association-mapping
 * @see https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/unitofwork-associations.html
 * ManyToMany with extra columns
 * @see https://symfonycasts.com/screencast/doctrine-relations/complex-many-to-many
 * 
 */
#[ORM\Entity(repositoryClass: SortableRepository::class)]
#[ORM\Table(name: '`between_many_sorted_item`')]
#[HasLifecycleCallbacks]
class ItemCollection implements ItemCollectionInterface
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WireEcollectionInterface::class, inversedBy: 'childs')]
    protected WireEcollectionInterface $parent;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WireItemInterface::class, inversedBy: 'parents')]
    protected WireItemInterface $child;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Assert\NotNull(groups: ['persist','update'])]
    #[Gedmo\SortableGroup]
    protected string $sortgroup;

    #[ORM\Column(updatable: false, nullable: false, unique: true)]
    #[Assert\NotNull(groups: ['persist','update'])]
    protected readonly string $euid;

    /**
     * @see https://github.com/doctrine-extensions/DoctrineExtensions/tree/main/src/Sortable
     * To move an item at the end of the list, you can set the position to `-1`:
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\SortablePosition]
    protected int $position;

    public function __construct(
        BetweenManyParentInterface $parent,
        BetweenManyChildInterface $child
    )
    {
        if(!($parent instanceof WireEcollectionInterface)) throw new Exception(vsprintf('Error %s line %d: the parent parameter must be an instance of %s', [__METHOD__, __LINE__, WireEcollectionInterface::class]));
        if(!($child instanceof WireItemInterface)) throw new Exception(vsprintf('Error %s line %d: the child parameter must be an instance of %s', [__METHOD__, __LINE__, WireItemInterface::class]));
        if($parent === $child) throw new Exception(vsprintf('Error %s line %d: the parent and child parameters must be different', [__METHOD__, __LINE__]));
        $this->euid = Encoders::geUniquid(static::class . '|');
        $this->parent = $parent;
        $this->child = $child;
        $this->child->attributeDefaultMainparent();
        $this->sortgroup = $this->parent->getSortgroup($this->child);
    }

    public function getEuid(): string
    {
        return $this->euid;
    }

    public function getParent(): WireEcollectionInterface
    {
        return $this->parent;
    }

    public function getChild(
        ?WireEcollectionInterface $temp_parent = null
    ): WireItemInterface
    {
        $this->child->setTempParent($temp_parent);
        return $this->child;
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

    #[ORM\PreUpdate]
    public function updateSortgroup(): static
    {
        $this->sortgroup = $this->parent->getSortgroup($this->child);
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

    #[ORM\PreRemove]
    public function preRemove(): static
    {
        $this->child->removeParent($this->parent);
        // if($this->child->getMainparent() === $this->parent) {
        //     $this->child->removeMainparent();
        // }
        return $this;
    }

}