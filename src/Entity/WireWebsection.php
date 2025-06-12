<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\PostEmbeded;
use Aequation\WireBundle\Component\TwigfileMetadata;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionTranslationInterface;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Tools\Files;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
// PHP
use InvalidArgumentException;

#[UniqueEntity(fields: ['name'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\TranslationEntity(class: WireWebsectionTranslationInterface::class)]
abstract class WireWebsection extends MappSuperClassEntity implements WireWebsectionInterface
{
    use Unamed;

    public const ICON = [
        'ux' => 'tabler:section',
        'fa' => 'fa-s'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    protected ?int $id = null;

    #[ORM\Column()]
    #[Assert\NotBlank(message: 'Le nom est obligatoire', groups: ['persist','update'])]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: WireMenuInterface::class, fetch: 'EAGER')]
    protected ?WireMenuInterface $mainmenu = null;

    #[ORM\Column()]
    #[Assert\Regex(pattern: Files::TWIGFILE_MATCH, match: true, message: 'Le format du fichier est invalide.', groups: ['persist','update'])]
    protected ?string $twigfile = null;

    #[ORM\Column]
    protected bool $prefered = false;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $title = null;

    #[ORM\Column]
    #[Gedmo\Translatable]
    protected array $content = [];

    #[ORM\Column(length: 32, nullable: false)]
    protected string $sectiontype;

    #[ORM\OneToMany(targetEntity: WireWebsectionTranslationInterface::class, mappedBy: 'object', cascade: ['persist', 'remove'])]
    protected $translations;

    public ?WireWebpageInterface $tempWebpage = null;
    protected readonly TwigfileMetadata $twigfileMetadata;


    public function __construct()
    {
        parent::__construct();
        $this->translations = new ArrayCollection();
    }


    public function __toString(): string
    {
        return empty($this->name) ? parent::__toString() : $this->name;
    }

    public function setTempWebpage(?WireWebpageInterface $webpage): static
    {
        $this->tempWebpage = $webpage;
        return $this; // --> IMPORTANT
    }

    public function getTempWebpage(): ?WireWebpageInterface
    {
        return $this->tempWebpage;
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

    public function getMainmenu(
        bool $useTempWebpage = false
    ): ?WireMenuInterface
    {
        if($this->mainmenu ?? null) {
            return $this->mainmenu;
        }
        return $useTempWebpage && $this->tempWebpage
            ? $this->tempWebpage->getMainmenu()
            : null;
    }

    public function setMainmenu(?WireMenuInterface $mainmenu): static
    {
        $this->mainmenu = $mainmenu;
        return $this;
    }

    public function getTwigfileChoices(): array
    {
        return $this->getEmbededStatus()->service->getWebsectionModels($this);
    }

    public function getTwigfileName(): ?string
    {
        return empty($this->twigfile)
            ? null
            : Files::stripTwigfile($this->twigfile, true);
    }

    public function getTwigfile(): ?string
    {
        return $this->twigfile;
    }

    public function setTwigfile(string $twigfile): static
    {
        $this->twigfile = $twigfile;
        $sectiontype = $this->getEmbededStatus()->service->getSectiontypeOfFile($this->twigfile);
        if(empty($sectiontype)) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: The sectiontype for file %s was not found.', [__FILE__, __LINE__, $this->twigfile]));
        }
        $this->setSectiontype($sectiontype);
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getTwigfileMetadata(): TwigfileMetadata
    {
        return $this->twigfileMetadata ??= new TwigfileMetadata($this);
    }

    public function getSectiontype(): string
    {
        return $this->sectiontype ??= $this->getTwigfileMetadata()->getDefaultSectiontype();
    }

    #[PostEmbeded(on: ['create'])]
    public function setDefaultSectiontype(): static
    {
        $this->sectiontype ??= $this->getTwigfileMetadata()->getDefaultSectiontype();
        return $this;
    }

    public function setSectiontype(string $sectiontype): static
    {
        $this->sectiontype = $sectiontype;
        return $this;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(WireWebsectionTranslationInterface $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

}