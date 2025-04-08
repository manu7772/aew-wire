<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Entity\interface\ItemCollectionInterface;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Entity\interface\WireItemTranslationInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Entity\trait\Datetimed;
use Aequation\WireBundle\Entity\trait\Enabled;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Service\interface\WireItemServiceInterface;
use Aequation\WireBundle\Tools\Encoders;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

// #[ORM\Entity(repositoryClass: WireItemRepository::class)]
#[ORM\Entity(repositoryClass: SortableRepository::class)]
#[ORM\Table(name: 'w_item')]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ClassCustomService(WireItemServiceInterface::class)]
#[Gedmo\TranslationEntity(class: WireItemTranslationInterface::class)]
// #[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !', groups: ['persist','update'])]
#[ORM\HasLifecycleCallbacks]
abstract class WireItem extends MappSuperClassEntity implements WireItemInterface
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
    #[Assert\NotBlank(message: 'Le nom est obligatoire', groups: ['persist','update'])]
    #[Gedmo\Translatable]
    protected ?string $name = null;

    #[ORM\OneToMany(targetEntity: ItemCollectionInterface::class, mappedBy: 'child', cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Valid(groups: ['persist','update'])]
    protected Collection $parents;
    public WireEcollectionInterface $tempParent;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Assert\Regex(pattern: Encoders::EUID_SCHEMA, message: 'parent EUID is invalide.', groups: ['persist','update'])]
    protected ?string $mainparent = null;

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

    public function getTempParent(): ?WireEcollectionInterface
    {
        return $this->tempParent ?? $this->getMainparent();
    }

    public function setTempParent(?WireEcollectionInterface $tempParent = null): static
    {
        $this->tempParent = $tempParent;
        return $this;
    }

    public function getPosition(?WireEcollectionInterface $parent = null): int|false
    {
        $parent ??= $this->getTempParent();
        if($parent) {
            foreach ($this->parents as $ic) {
                /** @var ItemCollectionInterface $ic */
                if($ic->getParent() === $parent) {
                    return $ic->getPosition();
                }
            }
        }
        return false;
    }

    public function getMainparent(): ?WireEcollectionInterface
    {
        if(!empty($this->mainparent)) {
            foreach ($this->getParents() as $parent) {
                /** @var WireEcollectionInterface $parent */
                if($parent->getEuid() === $this->mainparent) {
                    return $parent;
                }
            }
        }
        // Not found, so becomes null if not
        return $this->mainparent = null;
    }

    public function setMainparent(WireEcollectionInterface $mainparent): bool
    {
        if($this->addParent($mainparent)) {
            $this->mainparent = $mainparent->getEuid();
        }
        $this->attributeDefaultMainparent();
        return $this->mainparent === $mainparent->getEuid();
    }

    public function removeMainparent(): bool
    {
        if($mainparent = $this->getMainparent()) {
            $this->removeParent($mainparent);
        }
        return empty($this->mainparent);
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function attributeDefaultMainparent(): static
    {
        $this->getMainparent(); // --> set $this->mainparent to null if parent not found
        if(!$this->mainparent) {
            $firstparent = $this->parents->first();
            $this->mainparent = $firstparent ? $firstparent->getParent()->getEuid() : null;
        }
        if(!$this->mainparent && $this->parents->count() > 0) {
            throw new \LogicException(vsprintf('Error %s line %d: The mainparent for item %s was not found.', [__FILE__, __LINE__, $this->__toString()]));
        }
        return $this;
    }

    public function addParent(WireEcollectionInterface $parent): bool
    {
        if(!$this->hasParent($parent)) {
            $ic = new ItemCollection($parent, $this);
            $this->parents->add($ic);
        }
        $this->attributeDefaultMainparent();
        return $this->hasParent($parent);
    }

    public function getParents(): Collection
    {
        return $this->parents->map(
            fn(ItemCollectionInterface $ic) => $ic->getParent()
        );
    }

    public function hasParent(WireEcollectionInterface $parent): bool
    {
        return $this->getParents()->contains($parent);
    }

    public function removeParent(WireEcollectionInterface $parent): bool
    {
        $this->parents = $this->parents->filter(
            fn(ItemCollectionInterface $ic) => $ic->getParent() !== $parent
        );
        $this->attributeDefaultMainparent();
        return $this->hasParent($parent);
    }

    public function removeParents(): bool
    {
        $this->parents->clear();
        $this->mainparent = null;
        return $this->parents->isEmpty();
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