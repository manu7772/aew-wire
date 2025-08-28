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
    protected ?string $grant = null;

    public function __construct_grantable(): void
    {
        if(!($this instanceof TraitGrantableInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitGrantableInterface::class]));
    }

    public function isGranted(?UserInterface $user = null): bool
    {
        if($this->grant === null) return true;
        return $user->getRoles() && in_array($this->grant, $user->getRoles(), true);
    }

    public function getGrant(): ?string
    {
        return $this->grant;
    }

    public function setGrant(?string $grant = null): static
    {
        if($grant) {

        }
        $this->grant = $grant;
        return $this;
    }

}