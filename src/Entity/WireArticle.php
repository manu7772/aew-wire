<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Entity\interface\WireArticleInterface;
use Aequation\WireBundle\Entity\trait\Owner;
use Aequation\WireBundle\Entity\trait\Relinkable;
use Aequation\WireBundle\Entity\trait\Webpageable;
use Aequation\WireBundle\Repository\WireArticleRepository;
use Aequation\WireBundle\Service\interface\WireArticleServiceInterface;
use DateTimeInterface;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

abstract class WireArticle extends WireItem implements WireArticleInterface
{

    use Owner, Webpageable, Relinkable;

    public const ICON = [
        'ux' => 'tabler:article',
        'fa' => 'fa-regular fa-newspaper'
    ];


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

}
