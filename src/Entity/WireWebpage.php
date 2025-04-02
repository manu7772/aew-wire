<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\SerializationMapping;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Entity\trait\Prefered;
use Aequation\WireBundle\Tools\Files;
use Doctrine\Common\Collections\Collection;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity(fields: ['name'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
#[SerializationMapping(WireWebpage::ITEMS_ACCEPT)]
class WireWebpage extends WireEcollection implements WireWebpageInterface
{
    use Prefered;

    public const ICON = [
        'ux' => 'tabler:brand-webflow',
        'fa' => 'fa-w'
    ];
    public const SORT_BETWEEN_MANY_BY_CHILDS_CLASS = true;
    public const ITEMS_ACCEPT = [
        'sections'     => [
            'field' => 'Items',
            'require' => [WireWebsectionInterface::class],
        ],
    ];

    #[ORM\ManyToOne(targetEntity: WireMenuInterface::class)]
    protected WireMenuInterface $mainmenu;

    #[ORM\Column()]
    #[Assert\Regex(pattern: Files::TWIGFILE_MATCH, match: true, message: 'Le format du fichier est invalide.', groups: ['persist','update'])]
    protected ?string $twigfile = null;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Translatable]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $linktitle = null;

    #[ORM\Column]
    #[Gedmo\Translatable]
    protected array $content = [];


    public function getMainmenu(): WireMenuInterface
    {
        return $this->mainmenu;
    }

    public function setMainmenu(WireMenuInterface $mainmenu): static
    {
        $this->mainmenu = $mainmenu;
        return $this;
    }

    public function getSections(): Collection
    {
        return $this->getItems()->filter(fn($item) => $item instanceof WireWebsectionInterface);
    }

    public function setSections(iterable $sections): static
    {
        $this->removeSections();
        foreach ($sections as $section) {
            if($section instanceof WireWebsectionInterface) $this->addSection($section);
        }
        return $this;
    }

    public function addSection($section): static
    {
        $this->addItem($section);
        return $this;
    }

    public function removeSection($section): static
    {
        $this->removeItem($section);
        return $this;
    }

    public function removeSections(): static
    {
        foreach ($this->getSections() as $section) {
            $this->removeSection($section);
        }
        return $this;
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

    public function getLinktitle(): ?string
    {
        return $this->linktitle;
    }

    public function setLinktitle(?string $linktitle): static
    {
        $this->linktitle = $linktitle;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateLinkTitle(): static
    {
        if(empty($this->linktitle)) $this->linktitle = $this->title;
        $this->linktitle = trim($this->linktitle);
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

}