<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Service\interface\UnameServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
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
    protected Uname $uname;


    public function __construct_unamed(): void
    {
        if(!($this instanceof TraitUnamedInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitUnamedInterface::class]));
    }

    public function __clone_unamed(): void
    {
        $this->uname = null;
        $this->updateUname();
    }

    public function updateUname(
        string $uname = null
    ): static
    {
        if(!isset($this->uname)) {
            // Add new Uname
            /** @var Uname */
            $this->uname = $this->_estatus->appWire->get(UnameServiceInterface::class)->createEntity(Uname::class, null);
        }
        $this->uname->attributeEntity($this, $uname);
        return $this;
    }

    public function getUname(): UnameInterface
    {
        return $this->uname;
    }

}