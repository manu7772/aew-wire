<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Entity\interface\RelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
use Aequation\WireBundle\Entity\trait\Datetimed;
use Aequation\WireBundle\Entity\trait\Enabled;
use Aequation\WireBundle\Entity\trait\Owner;
use Aequation\WireBundle\Entity\trait\Slug;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Repository\WireItemRepository;
use Aequation\WireBundle\Service\interface\WireItemServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: WireItemRepository::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ORM\HasLifecycleCallbacks]
#[ClassCustomService(WireItemServiceInterface::class)]
class WireItem extends MappSuperClassEntity implements WireItemInterface
{
    use Slug, Datetimed, Enabled, Unamed;

    public const ICON = [
        'ux' => 'tabler:file',
        'fa' => 'fa-file'
    ];
    // public const SERIALIZATION_PROPS = ['id','euid','name','classname','shortname'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected $id = null;

    #[ORM\Column(length: 255)]
    protected ?string $name = null;

    #[ORM\ManyToMany(targetEntity: WireEcollectionInterface::class, inversedBy: 'items', fetch: 'EXTRA_LAZY')]
    #[Gedmo\SortableGroup]
    protected Collection $parents;

    #[ORM\Column]
    #[Gedmo\SortablePosition]
    protected int $position;

    #[ORM\OneToMany(targetEntity: WireRelinkInterface::class, mappedBy: 'itemowner', orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $relinks;


    public function __construct()
    {
        parent::__construct();
        $this->parents = new ArrayCollection();
        $this->relinks = new ArrayCollection();
    }

    public function __toString(): string
    {
        return empty($this->name) ? parent::__toString() : $this->name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);
        return $this;
    }

    public function addParent(WireEcollectionInterface $parent): static
    {
        if($parent === $this) {
            // Failed to add parent
            $this->removeParent($parent);
            return $this;
        }
        if(!$this->hasParent($parent)) {
            $this->parents->add($parent);
        }
        if(!($parent->hasItem($this) || $parent->addItem($this))) {
            // Failed to add parent
            $this->removeParent($parent);
            $parent->removeItem($this);
        }
        return $this;
    }

    public function getParents(): Collection
    {
        return $this->parents;
    }

    public function hasParent(WireEcollectionInterface $parent): bool
    {
        return $this->parents->contains($parent);
    }

    public function removeParent(WireEcollectionInterface $parent): static
    {
        $this->parents->removeElement($parent);
        if($parent->hasItem($this)) $parent->removeItem($this);
        return $this;
    }

    public function removeParents(): static
    {
        foreach ($this->parents->toArray() as $parent) {
            $this->removeParent($parent);
        }
        return $this;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getPosition(): int
    {
        return $this->position;
    }


}