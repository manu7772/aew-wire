<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\Uname;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute as Serializer;
// PHP
use Exception;

trait Unamed
{

    #[ORM\OneToOne(cascade: ['persist'], orphanRemoval: true, fetch: 'LAZY')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\Valid()]
    #[Serializer\Ignore]
    protected readonly Uname $uname;

    public ?string $_tempUname = null;


    public function __construct_unamed(): void
    {
        $this->slug = '-';
        $this->updateSlug = false;
        if(!($this instanceof TraitUnamedInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitUnamedInterface::class]));
    }

    public function autoUpdateUname(): static
    {
        return $this->updateUname();
    }

    public function __clone_unamed(): void
    {
        if(!empty($this->_tempUname)) $this->_tempUname = $this->_tempUname.' - copie'.rand(1000, 9999);
        $this->updateUname();
    }

    public function updateUname(
        string $uname = null
    ): static
    {
        // if(!isset($this->uname) || $this->_isClone()) $this->uname = $this->_service->getNew(Uname::class);
        if(empty($uname)) $uname = empty($this->_tempUname) ? null : $this->_tempUname;
        $this->_tempUname = $uname;
        $this->uname->attributeEntity($this, $uname);
        return $this;
    }

    public function getUname(): ?Uname
    {
        if(!isset($this->uname)) $this->updateUname();
        return $this->uname;
    }

}