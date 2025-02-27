<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;

trait Enabled
{

    public const INIT_ENABLED_ENABLED = true;

    #[ORM\Column]
    private bool $enabled = true;

    public function __construct_enabled(): void
    {
        $this->enabled = static::INIT_ENABLED_ENABLED;
        if(!($this instanceof TraitEnabledInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitEnabledInterface::class]));
    }

    public function isActive(): bool
    {
        return $this->isEnabled();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isDisabled(): bool
    {
        return !$this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function isSoftdeleted(): bool
    {
        return $this->softdeleted;
    }

    public function setSoftdeleted(bool $softdeleted): static
    {
        $this->softdeleted = $softdeleted;
        return $this;
    }

}