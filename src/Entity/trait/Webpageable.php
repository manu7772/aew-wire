<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitWebpageableInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Doctrine\DBAL\Types\Types;
// Symfony
use Doctrine\ORM\Mapping as ORM;
// PHP
use Exception;

trait Webpageable
{

    public const HTML_TYPE = null;

    #[ORM\ManyToOne(targetEntity: WireWebpageInterface::class)]
    #[ORM\JoinColumn(nullable: true)]
    protected ?WireWebpageInterface $webpage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $linktitle = null;

    #[ORM\Column]
    protected array $content = [];


    public function __construct_webpageable(): void
    {
        if(!($this instanceof TraitWebpageableInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitWebpageableInterface::class]));
    }

    public function isWebpageRequired(): bool
    {
        return true;
    }

    public function setWebpage(?WireWebpageInterface $webpage = null): static
    {
        $this->webpage = $webpage;
        return $this;
    }

    public function getWebpage(): ?WireWebpageInterface
    {
        return $this->webpage;
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