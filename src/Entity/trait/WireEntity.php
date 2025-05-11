<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Component\EntityEmbededStater;
use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Component\EntitySelfState;
use Aequation\WireBundle\Component\interface\EntityEmbededStaterInterface;
use Aequation\WireBundle\Component\interface\EntityEmbededStatusContainerInterface;
use Aequation\WireBundle\Component\interface\EntitySelfStateInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Tools\Encoders;
use Doctrine\DBAL\Types\Types;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\PropertyAccess\PropertyAccess;
// PHP
use ReflectionClass;
use Exception;

trait WireEntity
{

    // public const ICON = [
    //     'ux' => 'tabler:file',
    //     'fa' => 'fa-file'
    //     // Add other types and their corresponding icons here
    // ];
    // public const SERIALIZATION_PROPS = ['id'];

    #[ORM\Column(updatable: false, nullable: false, unique: true)]
    #[Assert\NotNull(groups: ['persist', 'update'])]
    // #[Assert\Regex(pattern: Encoders::EUID_SCHEMA)]
    protected string $euid;

    #[ORM\Column(updatable: false, nullable: false)]
    #[Assert\NotNull(groups: ['persist', 'update'])]
    protected readonly string $classname;

    #[ORM\Column(length: 32, updatable: false, nullable: false)]
    #[Assert\NotNull(groups: ['persist', 'update'])]
    protected readonly string $shortname;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    protected int $updates = 0;

    public readonly EntitySelfStateInterface $__selfstate;

    public function __construct_entity(): void
    {
        $this->initializeSelfstate();
        $this->getClassname();
        $this->getShortName();
        $this->euid = Encoders::geUniquid($this->classname.'|');
        // Other constructs
        $construct_methods = array_filter(get_class_methods($this), fn($method_name) => preg_match('/^__construct_(?!entity)/', $method_name));
        foreach ($construct_methods as $method) {
            $this->$method();
        }
        if (!($this instanceof BaseEntityInterface)) throw new Exception(vsprintf('Error %s line %d:%s- This entity %s sould implement %s!', [__METHOD__, __LINE__, PHP_EOL, static::class, BaseEntityInterface::class]));
    }



    /*************************************************************************************
     * UPDATES COUNT
     *************************************************************************************/

     #[ORM\PreUpdate]
    public function doUpdate(): void
    {
        $this->updates++;
    }

    public function getUpdates(): int
    {
        return $this->updates;
    }

    /*************************************************************************************
     * EMBEDED STATUS
     *************************************************************************************/

    public function initializeSelfstate(): void
    {
        $this->__selfstate_constructor_used ??= false;
        $this->__selfstate = new EntitySelfState($this);
    }

    public function getSelfState(): ?EntitySelfStateInterface
    {
        return $this->__selfstate;
    }

    public function hasEmbededStatus(): bool
    {
        return $this->getSelfState()->isReady();
    }

    public function getEmbededStatus(bool $load = true): null|EntityEmbededStatusContainerInterface|EntityEmbededStatusInterface|EntitySelfStateInterface
    {
        if($load && !$this->__selfstate->isReady()) {
            $this->__selfstate->getEmbededStatus();
        }
        return $this->__selfstate;
    }


    /*************************************************************************************
     * APP WIRE IDENTIFIERS
     *************************************************************************************/

    public function getEuid(): string
    {
        return $this->euid;
    }

    // public function setEuid(
    //     string $euid
    // ): static {
    //     if($this->__selfstate->isNew() || empty($this->euid ?? null)) {
    //         if(!Encoders::isEuidFormatValid($euid)) {
    //             throw new Exception(vsprintf('Error %s line %d: EUID "%s" is not valid!', [__METHOD__, __LINE__, $euid]));
    //         }
    //         $this->euid = $euid;
    //     }
    //     return $this;
    // }

    public function getUnameThenEuid(): string
    {
        return $this instanceof TraitUnamedInterface ? $this->getUname()->getId() : $this->getEuid();
    }

    public function defineUname(
        string $uname
    ): static {
        if ($this instanceof TraitUnamedInterface) {
            $this->updateUname($uname);
        }
        return $this;
    }

    public function getClassname(): string
    {
        if(!isset($this->classname)) {
            $rc = new ReflectionClass(static::class);
            $this->classname = $rc->getName();
        }
        return $this->classname;
    }

    public function getShortname(
        bool $lowercase = false
    ): string {
        if(!isset($this->shortname)) {
            $rc = new ReflectionClass(static::class);
            $this->shortname = $rc->getShortName();
        }
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
        foreach (constant('static::SERIALIZATION_PROPS') as $attr) {
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
        $accessor = PropertyAccess::createPropertyAccessorBuilder()->disableExceptionOnInvalidPropertyPath()->getPropertyAccessor();
        foreach ($data as $attr => $value) {
            if ($attr === 'id') {
                $this->id = $value;
                continue;
            }
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

    public static function getIcon(
        string $type = 'ux'
    ): string {
        return constant('static::ICON')[$type];
    }
}
