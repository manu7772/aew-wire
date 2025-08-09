<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
// PHP
use Exception;

trait Unamed
{

    #[ORM\OneToOne(targetEntity: UnameInterface::class, cascade: ['persist','remove'], orphanRemoval: true, fetch: 'EAGER')]
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
        if($uname instanceof UnameInterface) {
            // Uname object
            if($uname === $this->getUname()) {
                return $this;
            } else if(empty($this->uname ?? null)) {
                $this->uname = $uname;
            } else {
                dump(Objects::printUname($this->uname), Objects::printUname($uname));
                throw new Exception(vsprintf('Error %s line %d: in %s (is %s), can not replace the Uname object with %s!', [__METHOD__, __LINE__, static::class, $this->getSelfState()->isNew() ? 'new entity' : 'loaded from database', $uname->getSelfState()->isNew() ? 'a new Uname' : 'another Uname loaded from database']));
            }
        } else if(empty($uname)) {
            // NULL
            if($this->getSelfState()->isNew()) {
                $this->uname ??= new Uname();
                $this->uname->attributeEntity($this);
            } else {
                // $this->uname->setUname(null);
            }
        } else if(is_string($uname)) {
            // String
            if($this->getSelfState()->isNew()) {
                $this->uname ??= new Uname();
                // dump($this, $uname);
                $this->uname->attributeEntity($this, $uname);
                // if(!Encoders::isEuidFormatValid($uname) && !empty($uname)) dd($this, $uname);
            } else {
                $this->uname->setUname($uname);
            }
        }
        if(empty($this->uname)) {
            dump($this->getSelfState()->getReport(), Objects::printUname($uname));
            throw new Exception(vsprintf('Error %s line %d: in %s (is %s), Uname object could not be resolved!', [__METHOD__, __LINE__, static::class, $this->getSelfState()->isNew() ? 'new entity' : 'loaded from database']));
        }
        return $this;
    }

    public function setUname(UnameInterface|string $uname): static
    {
        $this->updateUname($uname);
        // dump($uname);
        return $this;
    }

    public function getUname(): ?UnameInterface
    {
        return $this->uname ?? null;
    }

    public function getUnameName(): ?string
    {
        return $this->uname?->getUname() ?? null;
    }

}