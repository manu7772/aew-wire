<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\WireEcollection;
use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Entity\interface\TraitCreatedInterface;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Entity\interface\TraitOwnerInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Entity\trait\Created;
use Aequation\WireBundle\Entity\trait\Enabled;
use Aequation\WireBundle\Entity\trait\Owner;
use Aequation\WireBundle\Entity\trait\Serializable;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Repository\WireItemRepository;
use Aequation\WireBundle\Service\interface\WireItemServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: WireItemRepository::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ORM\HasLifecycleCallbacks]
#[ClassCustomService(WireItemServiceInterface::class)]
abstract class WireItem extends MappSuperClassEntity implements WireItemInterface, TraitCreatedInterface, TraitEnabledInterface, TraitUnamedInterface, TraitOwnerInterface
{
    use Created, Enabled, Owner, Serializable, Unamed;

    public const ICON = 'tabler:file';
    public const FA_ICON = 'file';
    public const SERIALIZATION_PROPS = ['id','euid','name','classname','shortname'];


    #[ORM\Column(length: 255)]
    #[Serializer\Groups('index')]
    protected ?string $name = null;

    #[ORM\ManyToMany(targetEntity: WireEcollection::class, inversedBy: 'items', fetch: 'EXTRA_LAZY')]
    #[Serializer\Ignore]
    protected Collection $parents;

    public function __construct()
    {
        parent::__construct();
        $this->parents = new ArrayCollection();
    }

    public function __clone()
    {
        parent::__clone();
        $this->name .= ' - copie'.rand(1000, 9999);
        $this->removeParents();
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

    #[Serializer\Ignore]
    public function addParent(WireEcollection $parent): static
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

    #[Serializer\Ignore]
    public function getParents(): Collection
    {
        return $this->parents;
    }

    #[Serializer\Ignore]
    public function hasParent(
        WireEcollection $parent
    ): bool
    {
        return $this->parents->contains($parent);
    }

    #[Serializer\Ignore]
    public function removeParent(
        WireEcollection $parent
    ): static
    {
        $this->parents->removeElement($parent);
        if($parent->hasItem($this)) $parent->removeItem($this);
        return $this;
    }

    #[Serializer\Ignore]
    public function removeParents(): static
    {
        foreach ($this->parents->toArray() as $parent) {
            $this->removeParent($parent);
        }
        return $this;
    }


}