<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Entity\interface\ItemCollectionInterface;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Entity\interface\WireItemTranslationInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Entity\trait\Datetimed;
use Aequation\WireBundle\Entity\trait\Enabled;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Repository\WireItemRepository;
use Aequation\WireBundle\Service\interface\WireItemServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: WireItemRepository::class)]
#[ORM\Table(name: 'w_item')]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !')]
#[Gedmo\TranslationEntity(class: WireItemTranslationInterface::class)]
#[ClassCustomService(WireItemServiceInterface::class)]
#[ORM\HasLifecycleCallbacks]
class WireItem extends MappSuperClassEntity implements WireItemInterface
{
    use Datetimed, Enabled, Unamed;

    public const ICON = [
        'ux' => 'tabler:file',
        'fa' => 'fa-file'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    protected ?int $id = null;

    #[ORM\Column()]
    #[Gedmo\Translatable]
    protected ?string $name = null;

    #[ORM\OneToMany(targetEntity: ItemCollectionInterface::class, mappedBy: 'item', cascade: ['persist'], orphanRemoval: true)]
    protected Collection $parents;

    #[ORM\OneToMany(targetEntity: WireRelinkInterface::class, mappedBy: 'itemowner', orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $relinks;

    #[ORM\OneToMany(targetEntity: WireItemTranslationInterface::class, mappedBy: 'object', cascade: ['persist', 'remove'])]
    protected $translations;

    #[Gedmo\Translatable]
    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(length: 128, unique: true)]
    protected $slug;


    public function __construct()
    {
        parent::__construct();
        $this->parents = new ArrayCollection();
        $this->relinks = new ArrayCollection();
        $this->translations = new ArrayCollection();
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
        if($parent !== $this && !$this->hasParent($parent)) {
            $ic = new ItemCollection($parent, $this);
            $this->parents->add($ic);
        } else {
            $this->removeParent($parent);
        }
        return $this;
    }

    public function getParents(): Collection
    {
        return $this->parents->map(
            fn(ItemCollectionInterface $ic) => $ic->getEcollection()
        );
    }

    public function hasParent(WireEcollectionInterface $parent): bool
    {
        return $this->getParents()->contains($parent);
    }

    public function removeParent(WireEcollectionInterface $parent): static
    {
        $this->parents = $this->parents->filter(
            fn(ItemCollectionInterface $ic) => $ic->getEcollection() !== $parent
        );
        return $this;
    }

    public function removeParents(): static
    {
        $this->parents->clear();
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(WireTranslationInterface $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

}