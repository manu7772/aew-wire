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

    #[ORM\OneToOne(targetEntity: UnameInterface::class, cascade: ['persist'], orphanRemoval: true, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\Valid()]
    protected UnameInterface $uname;


    public function __construct_unamed(): void
    {
        if(!($this instanceof TraitUnamedInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitUnamedInterface::class]));
        $this->updateUname();
    }

    public function updateUname(
        null|UnameInterface|string $uname = null
    ): static
    {
        if(!isset($this->uname)) {
            $this->uname = $uname instanceof UnameInterface ? $uname : new Uname();
        } else if($uname instanceof UnameInterface) {
            if($this->uname->getSelfState()->isLoaded()) {
                // Can not remplace the Uname object if from database
                // throw new Exception(vsprintf('Error %s line %d: this class %s can not remplace the Uname object if from database!', [__METHOD__, __LINE__, static::class]));
                $this->uname->setUname($uname->getUname());
            } else {
                $this->uname = $uname;
            }
        }
        $this->uname->attributeEntity($this, $uname instanceof UnameInterface ? $uname->getUname() : $uname);
        return $this;
    }

    public function setUname(UnameInterface|string $uname): static
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
        return $this->uname?->getUname();
    }

}