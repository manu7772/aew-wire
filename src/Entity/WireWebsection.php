<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\PostEmbeded;
use Aequation\WireBundle\Component\TwigfileMetadata;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Tools\Files;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
// PHP
use InvalidArgumentException;

#[UniqueEntity(fields: ['name'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
class WireWebsection extends WireItem implements WireWebsectionInterface
{

    public const ICON = [
        'ux' => 'tabler:section',
        'fa' => 'fa-s'
    ];

    #[ORM\ManyToOne(targetEntity: WireMenuInterface::class)]
    protected WireMenuInterface $mainmenu;

    #[ORM\Column()]
    #[Assert\Regex(pattern: Files::TWIGFILE_MATCH, match: true, message: 'Le format du fichier est invalide.', groups: ['persist','update'])]
    protected ?string $twigfile = null;

    #[ORM\Column]
    protected bool $prefered = false;

    #[ORM\Column(length: 32, nullable: false)]
    protected string $sectiontype;

    protected readonly TwigfileMetadata $twigfileMetadata;


    public function getMainmenu(): WireMenuInterface
    {
        return $this->mainmenu;
    }

    public function setMainmenu(WireMenuInterface $mainmenu): static
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

}