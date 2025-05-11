<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitWebpageableInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Gedmo\Mapping\Annotation as Gedmo;
// PHP
use Exception;

trait Webpageable
{

    public const HTML_TYPE = null;

    #[ORM\ManyToOne(targetEntity: WireWebpageInterface::class)]
    #[ORM\JoinColumn(nullable: true)]
    protected ?WireWebpageInterface $webpage = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $title = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $linktitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    protected null|string|iterable $content = null;


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
        if(empty($this->linktitle) && !empty($this->title)) {
            $this->setLinktitle($this->title);
        }
        return $this;
    }

    public function getContent(): ?array
    {
        return empty($this->content) ? null : json_decode($this->content, true);
    }

    public function setContent(null|string|array $content): static
    {
        $this->content = empty($content) ? null : json_encode((array) $content);
        return $this;
    }

}