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

    #[ORM\ManyToOne(targetEntity: WireUserInterface::class)]
    #[ORM\JoinColumn(name: 'owner_entity', nullable: true)]
    #[CurrentUser(required: true)]
    protected ?WireUserInterface $owner = null;

    public function __construct_owner(): void
    {
        if(!($this instanceof TraitOwnerInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitOwnerInterface::class]));
    }

    public function isOwnerRequired(): bool
    {
        return false;
    }

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