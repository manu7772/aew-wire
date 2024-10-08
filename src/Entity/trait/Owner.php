<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitOwnerInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Attribute\CurrentUser;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Exception;

trait Owner
{

    public function __construct_owner(): void
    {
        if(!($this instanceof TraitOwnerInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitOwnerInterface::class]));
    }

    #[ORM\ManyToOne(targetEntity: WireUserInterface::class)]
    #[ORM\JoinColumn(name: 'owner_entity')]
    #[CurrentUser()]
    protected ?WireUserInterface $owner = null;

    public function getOwner(): ?WireUserInterface
    {
        return $this->owner;
    }

    public function setOwner(
        ?WireUserInterface $owner
    ): static
    {
        $this->owner = $owner;
        return $this;
    }

}