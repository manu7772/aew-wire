<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\Twigfile;
use Aequation\WireBundle\Dto\WireWebpageDto;
use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Attribute\AdminGroup;
use Aequation\WireBundle\Entity\trait\Prefered;
use Aequation\WireBundle\Attribute\WireRelationMapping;
use Aequation\WireBundle\Component\Wpexpose;
use Aequation\WireBundle\Component\interface\WpexposeInterface;
use Aequation\WireBundle\Entity\trait\BetweenSortedParent;
use Aequation\WireBundle\Entity\interface\TwigfileInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\interface\TextContentsInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Entity\interface\BetweenSortedChildInterface;
use Aequation\WireBundle\Entity\interface\WpexposeCollectionInterface;
use Aequation\WireBundle\Entity\interface\WebsectionCollectionInterface;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity(fields: ['name'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
#[WireRelationMapping(WireWebpage::ITEMS_ACCEPT)]
#[AdminGroup(group: 'WireWebpage', order: 5, icon: 'tabler:letter-w')]
abstract class WireWebpage extends WireItem implements WireWebpageInterface
{
    use Prefered, BetweenSortedParent;

    public const ICON = [
        'ux' => 'tabler:letter-w',
        'fa' => 'fa-w'
    ];
    public const SORT_BETWEEN_MANY_BY_CHILDS_CLASS = true;
    public const ITEMS_ACCEPT = [
        // 'sections' => [
        //     'field' => 'sections',
        //     'require' => [WireWebsectionInterface::class],
        // ],
    ];
    public const MAX_PREFERED = 1; // 1 is the maximum number of prefered sections in the database
    public const MIN_PREFERED = 1; // 1 is the minimum number of prefered sections in the database
    public const BY_PREFERED = [];

    #[ORM\OneToMany(targetEntity: WebsectionCollectionInterface::class, mappedBy: 'webpage', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EAGER')]
    #[ORM\OrderBy(['position' => 'DESC'])]
    protected Collection $sections;
    protected Collection $temp_sections;

    #[ORM\ManyToOne(targetEntity: WireMenuInterface::class, fetch: 'EAGER')]
    protected ?WireMenuInterface $mainmenu = null;

    #[ORM\Embedded(Twigfile::class)]
    protected TwigfileInterface $twigfile;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $linktitle = null;

    #[ORM\Embedded(TextContents::class)]
    #[Gedmo\Translatable]
    protected TextContentsInterface $content;

    #[ORM\Embedded(WpexposeCollection::class)]
    protected WpexposeCollectionInterface $wpexposes;


    public function __construct()
    {
        parent::__construct();
        $this->sections = new ArrayCollection();
        $this->twigfile = new Twigfile();
        $this->content = new TextContents();
        $this->wpexposes = new WpexposeCollection();
    }

    public static function _createFromDto(WireWebpageDto $dto): static
    {
        $instance = new static();
        foreach ($dto->toArray() as $property => $value) {
            $instance->{$property} = $value;
        }
        return $instance;
    }

    public function _updateFromDto(WireWebpageDto $dto): static
    {
        foreach ($dto->toArray() as $property => $value) {
            $this->{$property} = $value;
        }
        return $this;
    }

    public function _createDto(array $options = []): WireWebpageDto
    {
        return new WireWebpageDto($this, $this->getEmbededStatus()->getWireEm(), $options);
    }

    public function getMaxPrefered(): ?int
    {
        return static::MAX_PREFERED;
    }

    public function getMinPrefered(): ?int
    {
        return static::MIN_PREFERED;
    }

    public function getPreferedBy(): array
    {
        return static::BY_PREFERED;
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
    
    public function getSections(?string $type = null, bool $passBetweens = true): Collection
    {
        $ws = $passBetweens ? $this->sections->map(fn(WireWebpageWebsectionCollection $section) => $section->getChild()->setTempWebpage($this)) : $this->sections;
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

    public function getSection(string $type, bool $passBetween = true): ?WireWebsectionInterface
    {
        $sections = $this->getSections($type, $passBetween);
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

    public function getSectionPosition(WireWebsectionInterface $section): int|false
    {
        foreach ($this->sections as $ic) {
            /** @var WireWebpageWebsectionCollection $ic */
            if($ic->getChild() === $section) {
                return $ic->getPosition();
            }
        }
        return false;
    }

    public function setSectionPosition(WireWebsectionInterface $section, int $position): bool
    {
        foreach ($this->sections as $ic) {
            /** @var WireWebpageWebsectionCollection $ic */
            if($ic->getChild() === $section) {
                $ic->setPosition($position);
                return true;
            }
        }
        return false;
    }

    public function getTwigfileName(): ?string
    {
        return $this->twigfile->isEmpty() ? null : $this->twigfile->getName();
    }

    public function getTwigfile(): ?TwigfileInterface
    {
        return $this->twigfile->isEmpty() ? null : $this->twigfile;
    }

    public function setTwigfile(TwigfileInterface|string $twigfile): static
    {
        if(is_string($twigfile)) {
            if(!isset($this->twigfile)) {
                $this->twigfile = new Twigfile($twigfile);
            } else {
                $this->twigfile->setPath($twigfile);
            }
            return $this;
        }
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
        if(empty($this->linktitle ?? null)) {
            $this->setLinktitle($this->title ?? $this->name);
        }
        return $this;
    }

    public function getContent(): TextContentsInterface
    {
        return $this->content;
    }

    public function setContent(TextContentsInterface $content): static
    {
        $this->content = $content;
        return $this;
    }

    // Sortgroup
    public function getSortgroup(?BetweenSortedChildInterface $child = null): string
    {
        return $this->getEuid().(static::SORT_BETWEEN_MANY_BY_CHILDS_CLASS && $child instanceof WireWebsectionInterface ? '@'.$child->getSectiontype() : '');
    }


    /**************************************************************************************************************/
    /** EXPOSES                                                                                                   */
    /**************************************************************************************************************/

    public function isAvailableForExpose(string|object $item, ?bool $plural = null): bool
    {
        return $this->wpexposes->isAvailableFor($item, $plural);
    }

    public function getWpexposes(): WpexposeCollectionInterface
    {
        return $this->wpexposes;
    }

    public function setWpexposes(WpexposeCollectionInterface $wpexposes): static
    {
        $this->wpexposes = $wpexposes;
        return $this;
    }

    public function addWpexpose(...$params): static
    {
        // dump($params);
        if(array_is_list($params)) {
            foreach ($params as $key => $value) {
                if($value instanceof WpexposeInterface) {
                    if($value->isValid() && !$this->wpexposes->contains($value)) {
                        $this->wpexposes->add($value);
                    }
                } else if(is_array($value)) {
                    $citem = new Wpexpose($value);
                    if($citem->isValid()) {
                        $this->wpexposes->add($citem);
                    } else {
                        unset($citem);
                    }
                } else {
                    throw new \InvalidArgumentException(sprintf('Error %s line %d: Wpexpose expects an array or a JSON string, got "%s"', __METHOD__, __LINE__, gettype($value)));
                }
            }
        } else if(is_array($params)) {
            $citem = new Wpexpose($params);
            if($citem->isValid()) {
                $this->wpexposes->add($citem);
            } else {
                unset($citem);
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Error %s line %d: Wpexpose expects an array or a JSON string, got "%s"', __METHOD__, __LINE__, gettype($params)));
        }
        return $this;
    }

    public function hasWpexpose(...$params): bool
    {
        return $this->wpexposes->exists(fn(WpexposeInterface $wexpose) => $wexpose->isValid() && $wexpose->matchParams(...$params));
    }

    public function getWpexpose(...$params): ?WpexposeInterface
    {
        foreach ($this->wpexposes as $wpexpose) {
            /** @var WpexposeInterface $wpexpose */
            if ($wpexpose->isValid() && $wpexpose->matchParams(...$params)) {
                return $wpexpose;
            }
        }
        return null;
    }

    public function removeWpexpose(...$params): static
    {
        foreach ($this->wpexposes as $key => $wpexpose) {
            /** @var WpexposeInterface $wpexpose */
            if ($wpexpose->matchParams(...$params)) {
                $this->wpexposes->removeElement($wpexpose);
            }
        }
        return $this;
    }

    #[ORM\PostLoad]
    public function postLoad_wpexpose(): void
    {
        // dump($this->wpexposes);
        $this->wpexposes->regularize();
        // dump($this->wpexposes);
    }



}