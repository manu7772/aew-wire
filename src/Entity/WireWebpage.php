<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\AdminGroup;
use Aequation\WireBundle\Attribute\WireRelationMapping;
use Aequation\WireBundle\Entity\interface\WebsectionCollectionInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Entity\trait\Prefered;
use Aequation\WireBundle\Tools\Files;
use Aequation\WireBundle\Tools\Strings;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Twig\Markup;

#[UniqueEntity(fields: ['name'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
#[WireRelationMapping(WireWebpage::ITEMS_ACCEPT)]
#[AdminGroup(group: 'WireWebpage', order: 5, icon: 'tabler:letter-w')]
abstract class WireWebpage extends WireItem implements WireWebpageInterface
{
    use Prefered;

    public const ICON = [
        'ux' => 'tabler:letter-w',
        'fa' => 'fa-w'
    ];
    public const SORT_BETWEEN_MANY_BY_CHILDS_CLASS = true;
    public const ITEMS_ACCEPT = [
        'websections' => [
            'field' => 'sections',
            'require' => [WireWebsectionInterface::class],
        ],
    ];
    public const MAX_PREFERED = 1; // 1 is the maximum number of prefered sections in the database
    public const MIN_PREFERED = 1; // 1 is the minimum number of prefered sections in the database

    #[ORM\OneToMany(targetEntity: WebsectionCollectionInterface::class, mappedBy: 'webpage', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EAGER')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $sections;
    protected Collection $temp_sections;

    #[ORM\ManyToOne(targetEntity: WireMenuInterface::class, fetch: 'EAGER')]
    protected ?WireMenuInterface $mainmenu;

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

    public function getMaxPrefered(): ?int
    {
        return static::MAX_PREFERED;
    }

    public function getMinPrefered(): ?int
    {
        return static::MIN_PREFERED;
    }

    public function getMainmenu(): ?WireMenuInterface
    {
        return $this->mainmenu ?? null;
    }

    public function setMainmenu(?WireMenuInterface $mainmenu): static
    {
        $this->mainmenu = $mainmenu;
        return $this;
    }

    #[ORM\PostLoad]
    public function initTempSections(): void
    {
        $this->temp_sections = new ArrayCollection($this->sections->toArray());
    }

    public function findTempSection(
        WireWebsectionInterface|WireWebpageWebsectionCollection $section
    ): ?WireWebpageWebsectionCollection
    {
        if(!$section->getSelfState()->isNew() && !$this->getSelfState()->isNew()) {
            foreach ($this->temp_sections as $temp_section) {
                /** @var WireWebpageWebsectionCollection $temp_section */
                if($temp_section->getChild(false) === $section || $temp_section === $section) {
                    return $temp_section;
                }
            }
        }
        return null;
    }
    
    public function getSections(?string $type = null): Collection
    {
        $ws = $this->sections->map(fn(WireWebpageWebsectionCollection $section) => $section->getChild()->setTempWebpage($this));
        return empty($type)
            ? $ws
            : $ws->filter(fn(WireWebsectionInterface $section) => $section->getSectiontype() === $type);
    }

    public function setSections(iterable $sections): static
    {
        $this->removeSections();
        foreach ($sections as $section) {
            $this->addSection($section);
        }    
        return $this;
    }    

    public function getSection(string $type): ?WireWebsectionInterface
    {
        $sections = $this->getSections($type);
        return $sections->isEmpty() ? null : $sections->first();
    }

    public function addSection(WireWebsectionInterface|WireWebpageWebsectionCollection $section): bool
    {
        if(!$this->hasSection($section)) {
            $new_section = $this->findTempSection($section);
            $new_section ??= $section instanceof WebsectionCollectionInterface ? $section : new WireWebpageWebsectionCollection($this, $section);
            if(!$this->sections->contains($new_section)) {
                $this->sections->add($new_section);
            }
        }
        return $this->hasSection($section);
    }

    public function hasSection(WireWebsectionInterface|WireWebpageWebsectionCollection $section): bool
    {
        foreach ($this->sections as $ic) {
            /** @var WebsectionCollectionInterface $ic */
            if($ic->getChild(false) === $section || $ic === $section || ($section instanceof WebsectionCollectionInterface && $ic->getChild(false) === $section->getChild(false))) {
                return true;
            }
        }
        return false;
    }

    public function removeSection(WireWebsectionInterface|WireWebpageWebsectionCollection $section): bool
    {
        if($section instanceof WireWebsectionInterface) {
            foreach ($this->sections as $ic) {
                /** @var WireWebpageWebsectionCollection $ic */
                if($ic->getChild(false) === $section) {
                    return $this->removeSection($ic);
                }
            }
        }
        $this->sections->removeElement($section);
        return !$this->hasSection($section);
    }

    public function removeSections(): static
    {
        foreach ($this->sections as $section) {
            $this->removeSection($section);
        }
        return $this;
    }

    // public function getWebsections(?string $type = null): Collection
    // {
    //     return $this->getSections($type);
    // }

    // public function getWebsection(string $type): ?WireWebsectionInterface
    // {
    //     foreach ($this->sections as $section) {
    //         if($section->getWebsection()->getSectiontype() === $type) {
    //             return $section->getWebsection();
    //         }
    //     }
    //     return null;
    // }

    // public function setWebsections(Collection $sections): static
    // {
    //     return $this->setSections($sections);
    // }

    // public function hasWebsection(WireWebsectionInterface $section): bool
    // {
    //     return $this->hasSection($section);
    // }

    // public function addWebsection(WireWebsectionInterface $section): bool
    // {
    //     return $this->addSection($section);
    // }

    // public function removeWebsection(WireWebsectionInterface $section): bool
    // {
    //     return $this->removeSection($section);
    // }

    // public function removeWebsections(): static
    // {
    //     return $this->removeSections();
    // }

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
        return $this->linktitle ?? $this->title;
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

    public function getContentToString(string $join = "\n"): ?string
    {
        $string = trim(implode($join, $this->content));
        return empty($string) ? null : $string;
    }

    public function getContentToHtml(string $join = "\n"): ?Markup
    {
        $string = $this->getContentToString($join);
        return empty($string) ? null : Strings::markup(nl2br($string));
    }

    public function setContent(array $content): static
    {
        $this->content = $content;
        return $this;
    }

}