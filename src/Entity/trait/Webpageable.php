<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitWebpageableInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Tools\Objects;
use Aequation\WireBundle\Tools\Strings;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
// PHP
use Exception;
use Twig\Markup;

trait Webpageable
{

    public const HTML_TYPE = null;
    public const WP_DEFAULT_UNAME = null; // Uname of the default Webpage for this entity

    #[ORM\ManyToOne(targetEntity: WireWebpageInterface::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true)]
    protected ?WireWebpageInterface $webpage = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Gedmo\Translatable]
    #[Assert\NotNull(message: 'Le titre est obligatoire', groups: ['persist','update'])]
    protected string $title;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Gedmo\Translatable]
    #[Assert\NotNull(message: 'Le lien titre est obligatoire', groups: ['persist','update'])]
    protected string $linktitle;

    #[ORM\Column(type: Types::JSON, nullable: false)]
    #[Gedmo\Translatable]
    protected array $content = [];


    public function __construct_webpageable(): void
    {
        if(!($this instanceof TraitWebpageableInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitWebpageableInterface::class]));
    }

    public static function getDefaultWebpageUname(): ?string
    {
        return static::WP_DEFAULT_UNAME ?: 'wp_page_'.strtolower(Objects::getShortname(static::class));
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

    public function hasWebpage(): bool
    {
        return $this->webpage instanceof WireWebpageInterface;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTitles(): void
    {
        if(empty($this->title ?? null) && !empty($this->linktitle ?? null)) {
            $this->setTitle($this->linktitle);
        }
        if(empty($this->linktitle ?? null) && !empty($this->title ?? null)) {
            $this->setLinktitle($this->title);
        }
        if(empty($this->linktitle ?? null) && empty($this->title ?? null)) {
            if(property_exists($this, 'name') && !empty($this->name ?? null)) {
                $this->setTitle($this->name);
                $this->setLinktitle($this->name);
            }
        }
    }

    public function getLinktitle(): string
    {
        return $this->linktitle ?? '';
    }

    public function setLinktitle(string $linktitle): static
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

    public function setContent(?array $content): static
    {
        $this->content = $content ?? [];
        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function getContentToString(string $join = "\n", bool $striptags = true): ?string
    {
        $string = trim(implode($join, $this->content));
        return empty($string) ? null : $string;
    }

    public function getContentToHtml(string $join = "<br>"): ?Markup
    {
        $string = $this->getContentToString($join, false);
        return empty($string) ? null : Strings::markup(nl2br($string));
    }

}