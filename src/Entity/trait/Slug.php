<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitSlugInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
// PHP
use Exception;

trait Slug
{

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Le Slug est vide !')]
    protected ?string $slug = null;

    protected ?bool $updateSlug = null;

    public function __construct_slug(): void
    {
        $this->slug = '-';
        $this->updateSlug = false;
        if(!($this instanceof TraitSlugInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitSlugInterface::class]));
    }

    public function __clone_slug(): void
    {
        $this->slug = '-';
        $this->updateSlug = true;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function setUpdateSlug(bool $updateSlug): static
    {
        $this->updateSlug = $updateSlug;
        if($this->isUpdateSlug()) $this->setUpdatedAt();
        return $this;
    }

    public function isUpdateSlug(): bool
    {
        return $this->updateSlug || $this->slug === '-' || empty($this->slug);
    }

}