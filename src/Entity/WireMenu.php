<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\RelationOrder;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\TraitScreenableInterface;
use Aequation\WireBundle\Entity\interface\TraitSlugInterface;
use Aequation\WireBundle\Entity\interface\WireCategoryInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Entity\trait\Prefered;
use Aequation\WireBundle\Entity\trait\Screenable;
use Aequation\WireBundle\Entity\trait\Slug;
use Aequation\WireBundle\Repository\WireMenuRepository;
use Aequation\WireBundle\Service\interface\WireMenuServiceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;

#[ORM\Entity(repositoryClass: WireMenuRepository::class)]
#[ORM\HasLifecycleCallbacks]
// #[UniqueEntity('name', message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[ClassCustomService(WireMenuServiceInterface::class)]
#[Slugable('name')]
abstract class WireMenu extends WireEcollection implements WireMenuInterface
{

    use Slug, Prefered, Screenable;

    public const ICON = "tabler:list";
    public const FA_ICON = "bars";
    public const ITEMS_ACCEPT = [
        'items'         => [WireMenuInterface::class, WireWebpageInterface::class],
        'categorys'     => [WireCategoryInterface::class],
    ];

    /**
     * @var Collection<int, WireCategoryInterface>
     */
    #[ORM\ManyToMany(targetEntity: WireCategoryInterface::class)]
    #[RelationOrder()]
    #[Serializer\MaxDepth(1)]
    protected Collection $categorys;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $linktitle = null;

    #[ORM\Column]
    protected bool $prefered = false;


    public function __construct()
    {
        parent::__construct();
        $this->categorys = new ArrayCollection();
    }

    public function __clone()
    {
        parent::__clone();
        $this->prefered = false;
    }

    public function getWebpages(
        bool $filterActives = false
    ): ArrayCollection
    {
        return $this->getItems($filterActives);
        // return $this->items->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()) && $item instanceof WebpageInterface; });
    }

    public function getSubmenus(
        bool $filterActives = false
    ): ArrayCollection
    {
        return $this->items->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()) && $item instanceof WireMenuInterface; });
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

    public function isPrefered(): bool
    {
        return $this->prefered;
    }

    public function setPrefered(bool $prefered): static
    {
        $this->prefered = $prefered;
        return $this;
    }

    /**
     * @return Collection<int, WireCategoryInterface>
     */
    #[Serializer\Ignore]
    public function getCategorys(): Collection
    {
        return $this->categorys;
    }

    #[Serializer\Ignore]
    public function addCategory(WireCategoryInterface $category): static
    {
        if($this->isAcceptsItemForEcollection($category, 'categorys') && is_a($this, $category->getType())) {
            if (!$this->categorys->contains($category)) {
                $this->categorys->add($category);
            }
        } else {
            $this->removeCategory($category);
            throw new Exception(vsprintf('Error %s line %d: category %s is not available for %s %s!', [__METHOD__, __LINE__, $category->__toString(), $this->getShortname(), $this->__toString()]));
        }
        return $this;
    }

    #[Serializer\Ignore]
    public function removeCategory(WireCategoryInterface $category): static
    {
        $this->categorys->removeElement($category);
        return $this;
    }

    #[Serializer\Ignore]
    public function removeCategorys(): static
    {
        foreach ($this->categorys as $category) {
            $this->removeCategory($category);
        }
        return $this;
    }

}
