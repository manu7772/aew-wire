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
// PHP
use ReflectionClass;
use Exception;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !')]
trait WireEntity
{

    public const ICON = [
        'ux' => 'tabler:file',
        'fa' => 'fa-file'
        // Add other types and their corresponding icons here
    ];
    public const SERIALIZATION_PROPS = ['id'];

    #[ORM\Column(length: 255, updatable: false, nullable: false)]
    #[Assert\NotNull()]
    protected readonly string $euid;

    #[ORM\Column(updatable: false, nullable: false)]
    #[Assert\NotNull()]
    protected readonly string $classname;

    #[ORM\Column(length: 32, updatable: false, nullable: false)]
    #[Assert\NotNull()]
    protected readonly string $shortname;

    public readonly EntityEmbededStatusInterface $__estatus;


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
        if(!($this instanceof WireEntityInterface)) throw new Exception(vsprintf('Error %s line %d:%s- This entity %s sould implement %s!', [__METHOD__, __LINE__, PHP_EOL, static::class, WireEntityInterface::class]));
    }

    public function setEmbededStatus(
        EntityEmbededStatusInterface $estatus
    ): void
    {
        if(!isset($this->__estatus)) {
            $this->__estatus = $estatus;
        } else if($this->__estatus !== $estatus) {
            if($this->__estatus->isDev()) throw new Exception(vsprintf('Error %s line %d:%s- This entity %s (%s - named "%s") already got %s!', [__METHOD__, __LINE__, PHP_EOL, static::class, $this->getShortname(), $this->__toString(), EntityEmbededStatusInterface::class]));
        }
    }

    public function hasEmbededStatus(): bool
    {
        return $this->getEmbededStatus() instanceof EntityEmbededStatusInterface;
    }

    public function getEmbededStatus(): ?EntityEmbededStatusInterface
    {
        return $this->__estatus ?? null;
    }

    public function getEuid(): string
    {
        return $this->euid;
    }

    public function getUnameThenEuid(): string
    {
        if($this instanceof TraitUnamedInterface) {
            $unameName = $this->getUname()->getUname();
        }
        return empty($unameName)
            ? $this->getEuid()
            : $unameName;
    }

    public function defineUname(
        string $uname
    ): static
    {
        if($this instanceof TraitUnamedInterface) {
            // if(strlen($uname) < 3) throw new Exception(vsprintf('Error %s line %d: Uname for %s must have at least 3 lettres, got "%s"!', [__METHOD__, __LINE__, static::class, $uname]));
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

    /**
     * get serialization data
     *
     * @return array
     */
    public function __serialize(): array
    {
        $array = ['id' => $this->id];
        $accessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        foreach (static::SERIALIZATION_PROPS as $attr) {
            $array[$attr] = $accessor->getValue($this, $attr);
        }
        return $array;
    }

    /**
     * unserialize data
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $accessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
        foreach ($data as $attr => $value) {
            $accessor->setValue($this, $attr, $value);
        }
    }

    /**
     * serialize
     *
     * @return string|null
     */
    public function serialize(): ?string
    {
        $array = $this->__serialize();
        return json_encode($array);
    }

    /**
     * unserialize
     *
     * @param string $data
     * @return void
     */
    public function unserialize(string $data): void
    {
        $data = json_decode($data, true);
        $this->__unserialize($data);
    }

    public function getIcon(
        string $type = 'ux'
    ): string
    {
        return static::ICON[$type];
    }


}