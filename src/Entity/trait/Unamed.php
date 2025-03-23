<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\Uname;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
// PHP
use Exception;

trait Unamed
{

    #[ORM\OneToOne(targetEntity: UnameInterface::class, cascade: ['persist'], orphanRemoval: true, fetch: 'LAZY')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\Valid()]
    protected UnameInterface $uname;


    public function __construct_unamed(): void
    {
        if(!($this instanceof TraitUnamedInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitUnamedInterface::class]));
        $this->updateUname();
    }

    public function updateUname(
        ?string $uname = null
    ): static
    {
        $this->uname ??= new Uname();
        $this->uname->attributeEntity($this, $uname);
        return $this;
    }

    public function setUname(string $uname): static
    {
        return $this->updateUname($uname);
    }

    // public function setUnameName(string $uname): static
    // {
    //     return $this->updateUname($uname);
    // }

    public function getUname(): ?UnameInterface
    {
        return $this->uname ?? null;
    }

    public function getUnameName(): ?string
    {
        return $this->uname->getUname();
    }

}