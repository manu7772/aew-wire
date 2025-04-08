<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkTranslationInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Entity\trait\Categorized;
use Aequation\WireBundle\Entity\trait\Datetimed;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Repository\WireRelinkRepository;
use Aequation\WireBundle\Service\Interface\WireRelinkServiceInterface;
use Aequation\WireBundle\Tools\Encoders;
// Symfony
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
// PHP
use Exception;

#[ORM\Entity(repositoryClass: WireRelinkRepository::class)]
#[ORM\Table(name: 'w_relink')]
#[ClassCustomService(WireRelinkServiceInterface::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[UniqueEntity(fields: ['name','parent'], message: 'Ce nom {{ value }} existe déjà', groups: ['persist','update'])]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\TranslationEntity(class: WireRelinkTranslationInterface::class)]
abstract class WireRelink extends MappSuperClassEntity implements WireRelinkInterface
{

    use Datetimed, Unamed, Categorized;

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
        'Nouvelle page' => '_blank',
    ];
    public const RELINK_TYPES = [
        'Url' => 'URL',
        'Reseau social' => 'RS',
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

    #[ORM\Column(length: 8, nullable: true)]
    protected ?string $target = null;

    // #[ORM\OneToOne(targetEntity: RelinkCollectionInterface::class, mappedBy: 'relink', cascade: ['persist'])]
    // protected RelinkCollectionInterface $parent;

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

    #[ORM\Column(type: Types::STRING, nullable: false, updatable: false)]
    #[Assert\Regex(pattern: Encoders::EUID_SCHEMA)]
    public readonly string $ownereuid;


    public function __construct()
    {
        if(!in_array(static::RELINK_TYPE, static::RELINK_TYPES)) throw new \Exception(vsprintf('Error %s line %d: static::RELINK_TYPE is invalid. Should be one of these: %s!', [__METHOD__, __LINE__, implode(', ', static::RELINK_TYPES)]));
        parent::__construct();
        $targets = static::TARGETS;
        $this->target = reset($targets);
    }

    public function __toString(): string
    {
        return (string)$this->getMainlink();
    }

    public function getALink(
        ?int $referenceType = null
    ): ?string
    {
        throw new Exception(vsprintf('Error %s line %d: please implement this in final entity', [__METHOD__, __LINE__]));
        // switch ($this->getRelinkType()) {
        //     case 'URL':
        //         if($this->isUrl()) {
        //             return $this->mainlink;
        //         } else if($this->isRoute()) {
        //             return $this->getEmbededStatus()->appWire->getUrlIfExists($this->mainlink, $this->params, $referenceType ?? Router::ABSOLUTE_PATH);
        //         }
        //         break;
        //     case 'ADDRESS':
        //         throw new Exception(vsprintf('Error %s line %d: please implement this in final entity', [__METHOD__, __LINE__]));
        //         // return $this->getMaplink();
        //         break;
        //     case 'EMAIL':
        //         return 'mailto:'.$this->mainlink;
        //         break;
        //     case 'PHONE':
        //         return 'tel:'.preg_replace('/[\s\t]/', '', $this->mainlink);
        //         break;
        // }
        // return null;
    }

    public function isUrl(): bool
    {
        return $this->getRelinkType() === 'URL' && preg_match('/^https?:\/\//', $this->mainlink);
    }

    public function isRoute(): bool
    {
        return $this->getRelinkType() === 'URL' && !!$this->isUrl();
    }

    public function isRs(): bool
    {
        return $this->getRelinkType() === 'RS';
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(WireTranslationInterface $t): static
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
        return $this;
    }

    public function setOwnereuid(TraitRelinkableInterface $owner): static
    {
        $this->ownereuid = $owner->getEuid();
        return $this;
    }

    public function getOwnereuid(): string
    {
        return $this->ownereuid;
    }

}
