<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Tools\Encoders;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;
// PHP
use ReflectionClass;
use Exception;

#[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !')]
trait WireEntity
{

    #[ORM\Column(length: 255, updatable: false, nullable: false)]
    #[Assert\NotNull()]
    #[Serializer\Groups('index')]
    protected readonly string $euid;

    #[ORM\Column(updatable: false, nullable: false)]
    #[Assert\NotNull()]
    #[Serializer\Groups('index')]
    protected readonly string $classname;

    #[ORM\Column(length: 32, updatable: false, nullable: false)]
    #[Assert\NotNull()]
    #[Serializer\Groups('index')]
    protected readonly string $shortname;

    #[Serializer\Ignore]
    public readonly EntityEmbededStatusInterface $_estatus;


    public function __construct_entity(): void
    {
        $rc = new ReflectionClass(static::class);
        $this->classname = $rc->getName();
        $this->shortname = $rc->getShortName();
        $this->euid = $this->getNewEuid();
        // Other constructs
        $construct_methods = array_filter(get_class_methods($this), fn($method_name) => preg_match('/^__construct_(?!entity)/', $method_name));
        foreach ($construct_methods as $method) {
            $this->$method();
        }
        if(!($this instanceof WireEntityInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s sould implement %s!', [__METHOD__, __LINE__, static::class, WireEntityInterface::class]));
    }

    public function setEmbededStatus(
        EntityEmbededStatusInterface $estatus
    ): void
    {
        $this->_estatus = $estatus;
    }

    public function getEmbededStatus(): EntityEmbededStatusInterface
    {
        return $this->_estatus;
    }

    // #[AppEvent(groups: [AppEvent::afterClone])]
    public function _removeIsClone(): static
    {
        $this->_setClone(false);
        return $this;
    }

    public function __clone_entity(): void
    {
        $this->euid = $this->getNewEuid();
        // $this->_service->setManagerToEntity($this);
        // Other clones
        $clone_methods = array_filter(get_class_methods($this), fn($method_name) => preg_match('/^__clone_(?!entity)/', $method_name));
        foreach ($clone_methods as $method) {
            $this->$method();
        }
    }

    public function getEuid(): string
    {
        return $this->euid;
    }

    public function getUnameThenEuid(): string
    {
        if($this instanceof TraitUnamedInterface) {
            return $this->getUname()->getUname();
        }
        return $this->getEuid();
    }

    public function defineUname(
        string $uname
    ): static
    {
        if($this instanceof TraitUnamedInterface) {
            if(strlen($uname) < 3) throw new Exception(vsprintf('Error %s line %d: Uname for %s must have at least 3 lettres, got "%s"!', [__METHOD__, __LINE__, static::class, $uname]));
            $this->updateUname($uname);
        }
        return $this;
    }

    private function getNewEuid(): string
    {
        return Encoders::geUniquid($this->classname.'|');
    }

    public function getClassname(): string
    {
        return $this->classname;
    }

    public function getShortname(
        bool $lowercase = false
    ): string
    {
        return $lowercase
            ? strtolower($this->shortname)
            : $this->shortname;
    }


}