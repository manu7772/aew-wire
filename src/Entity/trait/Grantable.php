<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitGrantableInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
// PHP
use Exception;

trait Grantable
{

    #[ORM\Column(type: 'string', length: 16, nullable: true, options: ['default' => null])]
    protected ?string $grantlevel = null;

    public function __construct_grantable(): void
    {
        if(!($this instanceof TraitGrantableInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitGrantableInterface::class]));
    }

    public function isGranted(?UserInterface $user = null): bool
    {
        if($this->grantlevel === null) return true;
        return $user->getRoles() && in_array($this->grantlevel, $user->getRoles(), true);
    }

    public function getGrantlevel(): ?string
    {
        return $this->grantlevel;
    }

    public function setGrantlevel(?string $grantlevel = null): static
    {
        $this->grantlevel = $grantlevel;
        return $this;
    }

}