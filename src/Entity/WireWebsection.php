<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Component\TwigfileMetadata;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Service\WireWebsectionService;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

class WireWebsection extends WireHtmlcode implements WireWebsectionInterface
{

    public const ICON = [
        'ux' => 'tabler:section',
        'fa' => 'fa-s'
    ];


    #[ORM\Column(length: 255)]
    #[Assert\Regex(pattern: WireWebsectionService::TWIGFILE_MATCH, match: true, message: 'Le format du fichier est invalide.')]
    protected ?string $twigfile = null;

    #[ORM\Column]
    protected bool $prefered = false;

    #[ORM\Column(length: 32, nullable: false)]
    protected string $sectiontype;

    protected readonly TwigfileMetadata $twigfileMetadata;

    public function __construct()
    {
        parent::__construct();
        // $this->categorys = new ArrayCollection();
    }


    public function getTwigfileChoices(): array
    {
        return $this->__estatus->service->getWebsectionModels($this);
    }

    public function getTwigfileName(): ?string
    {
        return empty($this->twigfile)
            ? null
            : WireWebsectionService::stripTwigfile($this->twigfile, true);
    }

    public function getTwigfile(): ?string
    {
        return $this->twigfile;
    }

    public function setTwigfile(string $twigfile): static
    {
        $this->twigfile = $twigfile;
        $sectiontype = $this->__estatus->service->getSectiontypeOfFile($this->twigfile);
        if(empty($sectiontype)) {
            throw new \InvalidArgumentException(vsprintf('Error %s line %d: The sectiontype in file %s was not found.', [__FILE__, __LINE__, $this->twigfile]));
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

    public function setSectiontype(string $sectiontype): static
    {
        $this->sectiontype = $sectiontype;
        return $this;
    }

}