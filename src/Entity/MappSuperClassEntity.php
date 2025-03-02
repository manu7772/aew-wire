<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Component\EntityEmbededStatus;
use Aequation\WireBundle\Entity\interface\TraitSerializableInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\trait\Serializable;
use Aequation\WireBundle\Entity\trait\WireEntity;
// Symfony
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\PropertyAccess\PropertyAccess;
// use Symfony\Component\Uid\UuidV7 as Uuid;
// PHP
use Throwable;

/**
 * Class MappSuperClassEntity
 * @package Aequation\WireBundle\Entity
 */
#[MappedSuperclass]
#[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !')]
abstract class MappSuperClassEntity implements WireEntityInterface
{
    use WireEntity;

    // public const ICON = [
    //     'ux' => 'tabler:question-mark',
    //     'fa' => 'fa-question'
    // ];
    // public const SERIALIZATION_PROPS = ['id','euid','unamename','classname','shortname'];

    // protected $id = null;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->__construct_entity();
    }

    /**
     * getId
     *
     * @return mixed
     */
    public function getId(): mixed
    {
        return $this->id ?? null;
    }

    // /**
    //  * get self
    //  *
    //  * @return static
    //  */
    // public function getSelf(): static
    // {
    //     return $this;
    // }

    /**
     * get as string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getShortname().(empty($this->getId()) ? '' : '@'.$this->getId());
    }


}