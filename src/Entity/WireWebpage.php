<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\SerializationMapping;
use Aequation\WireBundle\Entity\interface\WebsectionCollectionInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Entity\trait\Prefered;
use Aequation\WireBundle\Tools\Files;
use Doctrine\Common\Collections\ArrayCollection;
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
class WireWebpage extends WireItem implements WireWebpageInterface
{
    use Prefered;

    public const ICON = [
        'ux' => 'tabler:brand-webflow',
        'fa' => 'fa-w'
    ];
    public const SORT_BETWEEN_MANY_BY_CHILDS_CLASS = true;
    public const ITEMS_ACCEPT = [
        'websections' => [
            'field' => 'sections',
            'require' => [WireWebsectionInterface::class],
        ],
    ];

    #[ORM\OneToMany(targetEntity: WebsectionCollectionInterface::class, mappedBy: 'webpage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $sections;

    #[ORM\ManyToOne(targetEntity: WireMenuInterface::class)]
    protected WireMenuInterface $mainmenu;

    #[ORM\Column()]
    #[Assert\Regex(pattern: Files::TWIGFILE_MATCH, match: true, message: 'Le format du fichier est invalide.', groups: ['persist','update'])]
    protected ?string $twigfile = null;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $linktitle = null;

    #[ORM\Column]
    #[Gedmo\Translatable]
    protected array $content = [];


    public function __construct()
    {
        parent::__construct();
        $this->sections = new ArrayCollection();
    }

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
        return $this->sections;
    }

    public function getWebsections(): Collection
    {
        return $this->sections->map(fn(WireWebpageWebsectionCollection $section) => $section->getWebsection());
    }

    public function getWebsectionsByType(string $type): Collection
    {
        return $this->getWebsections()->filter(fn(WireWebsectionInterface $section) => $section->getSectiontype() === $type);
    }

    public function setWebsections(Collection $sections): static
    {
        $this->removeWebsections();
        foreach ($sections as $section) {
            if($section instanceof WireWebsectionInterface) $this->addWebsection($section);
        }
        return $this;
    }

    public function hasWebsection(WireWebsectionInterface $section): bool
    {
        return $this->getWebsections()->contains($section);
    }

    public function addWebsection(WireWebsectionInterface $section): bool
    {
        if(!$this->hasWebsection($section)) {
            $new_section = new WireWebpageWebsectionCollection($this, $section);
            $this->sections->add($new_section);
        }
        return $this->hasWebsection($section);
    }

    public function removeWebsection(WireWebsectionInterface $section): bool
    {
        foreach ($this->sections as $section_collection) {
            if($section_collection->getWebsection() === $section) {
                $this->sections->removeElement($section_collection);
                return true;
            }
        }
        return false;
    }

    public function removeWebsections(): static
    {
        foreach ($this->getWebsections() as $section) {
            $this->removeWebsection($section);
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