<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitPreferedInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
// PHP
use Exception;

trait Prefered
{

    #[ORM\Column]
    protected bool $prefered = false;

    public function __construct_prefered(): void
    {
        $this->prefered = false;
        if(!($this instanceof TraitPreferedInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitPreferedInterface::class]));
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

}