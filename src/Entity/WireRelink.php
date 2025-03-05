<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkTranslationInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Entity\trait\Datetimed;
use Aequation\WireBundle\Entity\trait\Slug;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Repository\WireRelinkRepository;
use Aequation\WireBundle\Service\Interface\WireRelinkServiceInterface;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Routing\Router;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: WireRelinkRepository::class)]
#[ORM\Table(name: 'w_relink')]
#[ClassCustomService(WireRelinkServiceInterface::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !', repositoryMethod: 'findBy')]
#[UniqueEntity(fields: ['name','itemowner'], message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[ORM\HasLifecycleCallbacks]
class WireRelink extends MappSuperClassEntity implements WireRelinkInterface
{

    use Datetimed, Unamed;

    public const ICON = [
        'ux' => 'tabler:link',
        'fa' => 'fa-link'
    ];
    /**
     * @see https://www.w3schools.com/tags/att_a_target.asp 
     * <a target="_blank|_self|_parent|_top|framename">
     */
    public const TARGETS = [
        'Même page' => '_self',
        'Nouvel onglet' => '_blank',
    ];
    public const RELINK_TYPES = [
        'Url' => 'URL',
        'Adresse' => 'ADDRESS',
        'Email' => 'EMAIL',
        'Téléphone' => 'PHONE',
    ];
    public const RELINK_TYPE = null;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    protected ?int $id = null;

    #[ORM\OneToMany(targetEntity: WireRelinkTranslationInterface::class, mappedBy: 'object', cascade: ['persist', 'remove'])]
    protected $translations;

    #[Gedmo\Translatable]
    #[Gedmo\Slug(fields: ['mainlink'])]
    #[ORM\Column(length: 128, unique: true)]
    protected $slug;

    /**
     * Main link, regarding the static::RELINK_TYPE
     * - URL: type url or route
     * - ADDRESS: type address
     * - EMAIL: type email
     * - PHONE: type phone
     */
    #[ORM\Column(type: Types::TEXT, nullable: false)]
    protected ?string $mainlink = null;

    #[ORM\Column]
    protected bool $prefered = false;

    #[ORM\Column(nullable: true)]
    protected ?array $params = null;

    #[ORM\Column(length: 16, nullable: true)]
    protected ?string $target = null;

    // #[ORM\ManyToOne(targetEntity: LaboRelink::class, inversedBy: 'relinks', fetch: 'LAZY')]
    // protected ?LaboRelink $parentrelink = null;

    // /**
    //  * @var Collection<int, LaboRelink>
    //  */
    // #[ORM\OneToMany(targetEntity: LaboRelink::class, mappedBy: 'parentrelink', fetch: 'EXTRA_LAZY')]
    // #[RelationOrder()]
    // protected Collection $relinks;

    #[ORM\ManyToOne(targetEntity: WireItemInterface::class, inversedBy: 'relinks', fetch: 'LAZY')]
    #[Gedmo\SortableGroup]
    protected WireItemInterface & TraitRelinkableInterface $itemowner;

    #[ORM\Column]
    protected bool $turboenabled = true;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $name = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $linktitle = null;

    #[ORM\Column]
    #[Gedmo\SortablePosition]
    protected int $position;

    // /**
    //  * @var Collection<int, WireCategoryInterface>
    //  */
    // #[ORM\ManyToMany(targetEntity: WireCategoryInterface::class)]
    // protected Collection $categorys;

    public function __construct()
    {
        if(!in_array(static::RELINK_TYPE, static::RELINK_TYPES)) throw new \Exception(vsprintf('Error %s line %d: static::RELINK_TYPE is invalid. Should be one of these: %s!', [__METHOD__, __LINE__, implode(', ', static::RELINK_TYPES)]));
        parent::__construct();
        // $this->categorys = new ArrayCollection();
        // $this->relinks = new ArrayCollection();
        $targets = static::TARGETS;
        $this->target = reset($targets);
    }

    public function __toString(): string
    {
        return (string)$this->getMainlink();
    }

    public function getALink(
        ?int $referenceType = Router::ABSOLUTE_PATH
    ): ?string
    {
        switch ($this->getRelinkType()) {
            case 'URL':
                /** @var FinalUrlinkInterface $this */
                if($this->isUrl()) {
                    return $this->mainlink;
                } else if($this->isRoute()) {
                    return $this->_service->getAppService()->getUrlIfExists($this->mainlink, $this->params, $referenceType);
                }
                break;
            case 'ADDRESS':
                /** @var FinalAddresslinkInterface $this */
                return $this->getMaplink();
                break;
            case 'EMAIL':
                /** @var FinalEmailinkInterface $this */
                'mailto:'.$this->mainlink;
                break;
            case 'PHONE':
                /** @var FinalPhonelinkInterface $this */
                'tel:'.preg_replace('/[\\s]/', '', $this->mainlink);
                break;
        }
        return null;
    }

    public function isUrl(): bool
    {
        return $this->getRelinkType() === 'URL' && preg_match('/^https?:\/\//', $this->mainlink);
    }

    public function isRoute(): bool
    {
        return $this->getRelinkType() === 'URL' && !!$this->isUrl();
    }

    public function isAddress(): bool
    {
        return $this->getRelinkType() === 'ADDRESS';
    }

    public function isEmail(): bool
    {
        return $this->getRelinkType() === 'EMAIL';
    }

    public function isPhone(): bool
    {
        return $this->getRelinkType() === 'PHONE';
    }

    public function getRelinkType(): ?string
    {
        return static::RELINK_TYPE;
    }

    public function getRelinkTypeChoices(): array
    {
        return static::RELINK_TYPES;
    }

    public function getMainlink(): ?string
    {
        return $this->mainlink;
    }

    public function setMainlink(?string $mainlink): static
    {
        $this->mainlink = $mainlink;
        return $this;
    }

    public function isPrefered(): bool
    {
        return $this->prefered;
    }

    public function setPrefered(bool $prefered): static
    {
        $this->prefered = $prefered;
        return $this;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function setParams(?array $params): static
    {
        $this->params = $params;
        return $this;
    }

    public function getTargetChoices(): array
    {
        return static::TARGETS;
    }

    public function getTarget(): ?string
    {
        if($this->isUrl() || $this->isRoute()) {
            return $this->target ?? '_self';
        }
        return null;
    }

    /**
     * Get target (over attribute) as "_self" if URL is website domain, or as "_blank" if URL is external
     * 
     * @return string|null
     */
    public function getLogicTarget(): ?string
    {
        if($this->isRoute()) return '_self';
        return $this->isUrl() ? $this->getTarget() : null;
    }

    public function setTarget(?string $target): static
    {
        $this->target = in_array($target, static::TARGETS) ? $target : null;
        return $this;
    }

    // public function getParentrelink(): ?static
    // {
    //     return $this->parentrelink;
    // }

    // public function setParentrelink(?LaboRelinkInterface $parentrelink): static
    // {
    //     $this->parentrelink = $parentrelink;
    //     return $this;
    // }

    // /**
    //  * @return Collection<int, LaboRelinkInterface>
    //  */
    // public function getRelinks(): Collection
    // {
    //     return $this->relinks;
    // }

    // public function addRelink(LaboRelinkInterface $child): static
    // {
    //     if (empty($this->parentrelink) && !$this->relinks->contains($child)) {
    //         $this->relinks->add($child);
    //         $child->setParentrelink($this);
    //     }
    //     return $this;
    // }

    // public function removeRelink(LaboRelinkInterface $child): static
    // {
    //     if ($this->relinks->removeElement($child)) {
    //         // set the owning side to null (unless already changed)
    //         if ($child->getParentrelink() === $this) {
    //             $child->setParentrelink(null);
    //         }
    //     }
    //     return $this;
    // }

    public function setTurboenabled(
        bool $turboenabled = true
    ): static
    {
        $this->turboenabled = $turboenabled;
        return $this;
    }

    public function isTurboenabled(): bool
    {
        return $this->turboenabled;
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

    public function getLinktitle(): ?string
    {
        return $this->linktitle;
    }

    public function setLinktitle(?string $linktitle): static
    {
        $this->linktitle = $linktitle;
        return $this;
    }


    public function getItemowner(): WireItemInterface & TraitRelinkableInterface
    {
        return $this->itemowner;
    }

    public function setItemowner(WireItemInterface & TraitRelinkableInterface $itemowner): static
    {
        $this->itemowner = $itemowner;
        if(!$itemowner->hasRelink($this)) $itemowner->addRelink($this);
        return $this;
    }

    // #[ORM\PrePersist]
    // #[ORM\PreUpdate]
    // public function updateLinkTitle(): static
    // {
    //     if(empty($this->linktitle)) $this->linktitle = $this->title;
    //     $this->linktitle = trim($this->linktitle);
    //     return $this;
    // }

    // /**
    //  * @return Collection<int, WireCategoryInterface>
    //  */
    // public function getCategorys(): Collection
    // {
    //     return $this->categorys;
    // }

    // public function addCategory(WireCategoryInterface $category): static
    // {
    //     if (!$this->categorys->contains($category)) {
    //         $this->categorys->add($category);
    //     }
    //     return $this;
    // }

    // public function removeCategory(WireCategoryInterface $category): static
    // {
    //     $this->categorys->removeElement($category);
    //     return $this;
    // }

    // public function removeCategorys(): static
    // {
    //     foreach ($this->categorys as $category) {
    //         /** @var WireCategoryInterface $category */
    //         $this->removeCategory($category);
    //     }
    //     return $this;
    // }

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
