<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Component\EntityEmbededStatus;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\trait\WireEntity;
// Symfony
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\PropertyAccess\PropertyAccess;
// PHP
use Throwable;

#[MappedSuperclass]
abstract class MappSuperClassEntity implements WireEntityInterface
{
    use WireEntity;

    public const ICON = 'tabler:question-mark';
    public const FA_ICON = 'question';
    public const SERIALIZATION_PROPS = ['id','euid','classname','shortname'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Serializer\Groups(['index'])]
    private ?int $id = null;

    public function __construct()
    {
        $this->__construct_entity();
    }

    public function __clone()
    {
        new EntityEmbededStatus($this, EntityEmbededStatus::ENTITY_STATUS_CLONING, $this->_estatus->appWire);
        $this->_estatus->setCloning();
        $this->id = null;
        $this->__clone_entity(); // ----> UPDATE $this->_appManaged;
        // if($this instanceof OwnerInterface) {
        //     $this->_service->defineEntityOwner($this, true);
        // }
        // $this->_setClone(false);
        // $this->_service->dispatchEvent($this, AppEvent::afterClone);
        // if($this->_service->isDev() && $this->_appManaged->entity !== $this) {
        //     throw new Exception(vsprintf('Error %s line %d: this %s "%s" (id:%s) owned %s is invalid (has other entity %s "%s" - id:%s)!', [__METHOD__, __LINE__, $this->getClassname(), $this, $this->getId() ?? 'null', AppEntityInfo::class, $this->_appManaged->entity->getClassname(), $this->_appManaged->entity, $this->_appManaged->entity->getId() ?? 'null']));
        // }
        $this->_estatus->setCloned();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSelf(): static
    {
        return $this;
    }

    public function __toString(): string
    {
        return $this->getShortname().(empty($this->getId()) ? '' : '@'.$this->getId());
    }

    public function __serialize(): array
    {
        $array = ['id' => $this->id];
        $accessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        foreach (static::SERIALIZATION_PROPS as $attr) {
            $array[$attr] = $accessor->getValue($this, $attr);
        }
        return $array;
    }

    public function __unserialize(array $data): void
    {
        $accessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        foreach ($data as $attr => $value) {
            try {
                $accessor->setValue($this, $attr, $value);
            } catch (Throwable $th) {
                $this->$attr = $value;
            }
        }
    }

    public function serialize(): ?string
    {
        $array = $this->__serialize();
        return json_encode($array);
    }

    public function unserialize(string $data): void
    {
        $data = json_decode($data, true);
        $this->__unserialize($data);
    }

}