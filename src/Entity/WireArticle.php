<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\trait\Owner;
use Aequation\WireBundle\Attribute\AdminGroup;
use Aequation\WireBundle\Attribute\WireRelationMapping;
use Aequation\WireBundle\Entity\trait\Relinkable;
use Aequation\WireBundle\Entity\trait\Categorized;
use Aequation\WireBundle\Entity\trait\Webpageable;
use Aequation\WireBundle\Entity\trait\BetweenSortedParent;
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
// PHP
use DateTimeInterface;

#[ORM\HasLifecycleCallbacks]
#[AdminGroup(group: 'Media', order: 8, icon: 'tabler:photo')]
#[WireRelationMapping(WireEcollection::ITEMS_ACCEPT)]
abstract class WireArticle extends WireItem implements WireArticleInterface
{

    use Owner, Webpageable, Relinkable, Categorized, BetweenSortedParent;

    public const ICON = [
        'ux' => 'tabler:article',
        'fa' => 'fa-regular fa-newspaper'
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
    public const SORT_BETWEEN_MANY_BY_CHILDS_CLASS = true;

    #[ORM\OneToMany(targetEntity: WireArticleRelinkCollection::class, mappedBy: 'parent', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(groups: ['persist','update'])]
    protected Collection $relinks;

    #[ORM\ManyToMany(targetEntity: WireFactoryInterface::class, mappedBy: 'articles')]
    protected Collection $factorys;

    public function __construct()
    {
        parent::__construct();
        $this->factorys = new ArrayCollection();
    }

    public function isActive(): bool
    {
        return parent::isActive() && !$this->isDeprecated();
    }

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $start = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $end = null;


    public function getStart(): ?DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(?DateTimeInterface $start): static
    {
        $this->start = $start;
        return $this;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(?DateTimeInterface $end): static
    {
        $this->end = $end;
        return $this;
    }

    public function isDeprecated(?DateTimeInterface $now = null): bool
    {
        $now = $now ?? new \DateTime();
        $deprecated = false;
        if($this->start) {
            $deprecated = $this->start > $now;
        }
        if($this->end) {
            $deprecated = $this->end < $now;
        }
        return $deprecated;
    }

    public function getFactorys(): Collection
    {
        return $this->factorys;
    }

    public function addFactory(WireFactoryInterface $factory): static
    {
        if (!$this->factorys->contains($factory)) {
            $this->factorys->add($factory);
            $factory->addArticle($this);
        }
        return $this;
    }

    public function removeFactory(WireFactoryInterface $factory): static
    {
        if ($this->factorys->removeElement($factory)) {
            $factory->removeArticle($this);
        }
        return $this;
    }

    // Sortgroup
    public function getSortgroup(?BetweenSortedChildInterface $child = null): string
    {
        return $this->getEuid().(static::SORT_BETWEEN_MANY_BY_CHILDS_CLASS && $child instanceof WireRelinkInterface ? '@'.$child->getShortname() : '');
    }


}
