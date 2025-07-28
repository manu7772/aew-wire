<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\AdminGroup;
use Aequation\WireBundle\Entity\interface\TraitCategorizedInterface;
use Aequation\WireBundle\Entity\interface\WireAddresslinkInterface;
use Aequation\WireBundle\Entity\interface\WireArticleInterface;
use Aequation\WireBundle\Entity\interface\WireEmailinkInterface;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\interface\WirePhonelinkInterface;
use Aequation\WireBundle\Entity\interface\WireUrlinkInterface;
use Aequation\WireBundle\Entity\trait\Categorized;
use Aequation\WireBundle\Entity\trait\Owner;
use Aequation\WireBundle\Entity\trait\Relinkable;
use Aequation\WireBundle\Entity\trait\Webpageable;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// PHP
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\HasLifecycleCallbacks]
#[AdminGroup(group: 'Media', order: 8, icon: 'tabler:photo')]
abstract class WireArticle extends WireItem implements WireArticleInterface
{

    use Owner, Webpageable, Relinkable, Categorized;

    #[ORM\ManyToMany(targetEntity: WireFactoryInterface::class, mappedBy: 'articles')]
    protected Collection $factorys;

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



}
