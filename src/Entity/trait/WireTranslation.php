<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Component\EntitySelfState;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Tools\Encoders;
use Doctrine\DBAL\Types\Types;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
// PHP
use ReflectionClass;
use Exception;

trait WireTranslation
{
    // public const ICON = [
    //     'ux' => 'tabler:flag',
    //     'fa' => 'fa-flag'
    //     // Add other types and their corresponding icons here
    // ];
    // public const SERIALIZATION_PROPS = ['id','locale','field','content'];

    // #[ORM\Column(updatable: false, nullable: false, unique: true)]
    // #[Assert\NotNull(groups: ['persist', 'update'])]
    // // #[Assert\Regex(pattern: Encoders::EUID_SCHEMA)]
    // protected string $euid;

    // #[ORM\Column(updatable: false, nullable: false)]
    // #[Assert\NotNull(groups: ['persist', 'update'])]
    // protected readonly string $classname;

    // #[ORM\Column(length: 32, updatable: false, nullable: false)]
    // #[Assert\NotNull(groups: ['persist', 'update'])]
    // protected readonly string $shortname;

    // #[ORM\Column(type: Types::INTEGER, nullable: false)]
    // protected int $updates = 0;

    // // public readonly EntityEmbededStatusInterface $__estatus;
    // public readonly EntitySelfState $__selfstate;


    // public function __construct_translation(): void
    // {
    //     // $this->doInitializeSelfState('new');
    //     // $rc = new ReflectionClass(static::class);
    //     // $this->classname = $rc->getName();
    //     // $this->shortname = $rc->getShortName();
    //     // $this->setEuid(Encoders::geUniquid($this->classname.'|'));
    //     // // Other constructs
    //     // $construct_methods = array_filter(get_class_methods($this), fn($method_name) => preg_match('/^__construct_(?!entity)/', $method_name));
    //     // foreach ($construct_methods as $method) {
    //     //     $this->$method();
    //     // }
    //     if (!($this instanceof BaseEntityInterface)) throw new Exception(vsprintf('Error %s line %d:%s- This entity %s sould implement %s!', [__METHOD__, __LINE__, PHP_EOL, static::class, BaseEntityInterface::class]));
    // }

    public function __toString(): string
    {
        return (string)$this->content;
    }


}
