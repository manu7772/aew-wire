<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Component\EntitySelfState;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
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

    public readonly EntityEmbededStatusInterface $__estatus;
    public readonly EntitySelfState $__selfstate;

    public function __construct_entity(): void
    {
        $this->doInitializeSelfState('new');
        $rc = new ReflectionClass(static::class);
        $this->classname = $rc->getName();
        $this->shortname = $rc->getShortName();
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
     * SELF STATE
     *************************************************************************************/

    public function doInitializeSelfState(
        string $state = 'auto',
        bool|string $debug = 'auto'
    ): void {
        if ('auto' === $state) {
            $state = empty($this->getId()) ? 'new' : 'loaded';
        }
        $this->__selfstate ??= new EntitySelfState($this, $state, $debug);
    }

    public function getSelfState(): ?EntitySelfState
    {
        return $this->__selfstate ?? null;
    }

    public function getSelfStateReport(
        bool $asString = false
    ): array|string
    {
        return $this->getSelfState()->getReport($asString);
    }


    /*************************************************************************************
     * EMBEDED STATUS
     *************************************************************************************/

    public function setEmbededStatus(
        EntityEmbededStatusInterface $estatus
    ): void {
        if (!isset($this->__estatus)) {
            $this->__estatus = $estatus;
        } else if ($this->getEmbededStatus() !== $estatus) {
            if ($this->getEmbededStatus()->isDev()) throw new Exception(vsprintf('Error %s line %d:%s- This entity %s (%s - named "%s") already got %s!', [__METHOD__, __LINE__, PHP_EOL, static::class, $this->getShortname(), $this->__toString(), EntityEmbededStatusInterface::class]));
        }
    }

    public function hasEmbededStatus(): bool
    {
        $__estatus = $this->__estatus ?? null;
        return $__estatus instanceof EntityEmbededStatusInterface;
    }

    public function getEmbededStatus(): ?EntityEmbededStatusInterface
    {
        return $this->__estatus ?? null;
    }

    public function getEuid(): string
    {
        return $this->euid;
    }

    public function setEuid(
        string $euid
    ): static {
        if($this->__selfstate->isNew() || empty($this->euid ?? null)) {
            if(!Encoders::isEuidFormatValid($euid)) {
                throw new Exception(vsprintf('Error %s line %d: EUID "%s" is not valid!', [__METHOD__, __LINE__, $euid]));
            }
            $this->euid = $euid;
        }
        return $this;
    }

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
        return $this->classname;
    }

    public function getShortname(
        bool $lowercase = false
    ): string {
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
