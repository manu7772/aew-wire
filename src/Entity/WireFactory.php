<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\AdminGroup;
use Aequation\WireBundle\Entity\trait\Prefered;
use Aequation\WireBundle\Entity\trait\Relinkable;
use Aequation\WireBundle\Entity\trait\Categorized;
use Aequation\WireBundle\Entity\trait\Webpageable;
use Aequation\WireBundle\Attribute\WireRelationMapping;
use Aequation\WireBundle\Entity\trait\BetweenSortedParent;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
use Aequation\WireBundle\Entity\interface\WireUrlinkInterface;
use Aequation\WireBundle\Entity\interface\WireArticleInterface;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\interface\WireEmailinkInterface;
use Aequation\WireBundle\Entity\interface\WirePhonelinkInterface;
use Aequation\WireBundle\Entity\interface\WireAddresslinkInterface;
use Aequation\WireBundle\Entity\interface\BetweenSortedChildInterface;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
// PHP
use Exception;

#[UniqueEntity(fields: ['name'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
#[WireRelationMapping(WireFactory::ITEMS_ACCEPT)]
#[AdminGroup(group: 'Persons', order: 1, icon: 'tabler:users-group')]
abstract class WireFactory extends WireItem implements WireFactoryInterface
{

    use Prefered, Webpageable, Relinkable, Categorized, BetweenSortedParent;

    public const ICON = [
        'ux' => 'tabler:building-factory-2',
        'fa' => 'fa-solid fa-industry'
    ];
    public const ITEMS_ACCEPT = [
        'addresses' => [
            'field' => 'relinks',
            'require' => [WireAddresslinkInterface::class],
        ],
        'phones' => [
            'field' => 'relinks',
            'require' => [WirePhonelinkInterface::class],
        ],
        'emails' => [
            'field' => 'relinks',
            'require' => [WireEmailinkInterface::class],
        ],
        'urls' => [
            'field' => 'relinks',
            'require' => [WireUrlinkInterface::class],
        ],
    ];
    public const MAX_PREFERED = 1; // 1 is the maximum number of prefered sections in the database
    public const MIN_PREFERED = 1; // 1 is the minimum number of prefered sections in the database
    public const BY_PREFERED = [];

    #[ORM\OneToMany(targetEntity: WireFactoryRelinkCollection::class, mappedBy: 'parent', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EAGER')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(groups: ['persist','update'])]
    protected Collection $relinks;

    #[ORM\ManyToMany(targetEntity: WireArticleInterface::class, inversedBy: 'factorys')]
    #[ORM\JoinColumn(name: 'factory_article')]
    protected Collection $articles;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Translatable]
    // #[Assert\NotBlank(message: 'La fonctionnalité est obligatoire', groups: ['persist','update'])]
    protected ?string $functionality = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $description = null;

    /**
     * @var Collection<int, WireUserInterface>
     */
    #[ORM\ManyToMany(targetEntity: WireUserInterface::class, inversedBy: 'factorys')]
    protected Collection $associates;

    public function __construct()
    {
        parent::__construct();
        $this->relinks = new ArrayCollection();
        $this->associates = new ArrayCollection();
        $this->articles = new ArrayCollection();
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

    public function getFunctionality(): ?string
    {
        return $this->functionality;
    }

    public function setFunctionality(?string $functionality = null): static
    {
        $this->functionality = $functionality;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description = null): static
    {
        $this->description = $description;
        return $this;
    }

    public function getAssociates(): Collection
    {
        return $this->associates;
    }

    public function addAssociate(WireUserInterface $associate): static
    {
        if (!$this->associates->contains($associate)) {
            $this->associates->add($associate);
        }
        if(!$associate->hasFactory($this)) {
            $associate->addFactory($this);
        }
        return $this;
    }

    public function removeAssociate(WireUserInterface $associate): static
    {
        $this->associates->removeElement($associate);
        if($associate->hasFactory($this)) {
            $associate->removeFactory($this);
        }
        return $this;
    }

    public function hasAssociate(WireUserInterface $associate): bool
    {
        return $this->associates->contains($associate);
    }

    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(WireArticleInterface $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->addFactory($this);
        }
        return $this;
    }

    public function removeArticle(WireArticleInterface $article): static
    {
        if ($this->articles->removeElement($article)) {
            $article->removeFactory($this);
        }
        return $this;
    }

    // Sortgroup
    public function getSortgroup(?BetweenSortedChildInterface $child = null): string
    {
        return $this->getEuid().($child instanceof WireRelinkInterface ? '@'.$child->getShortname() : '');
    }

}
