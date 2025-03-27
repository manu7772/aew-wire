<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\trait\WireEntity;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class MappSuperClassEntity
 * @package Aequation\WireBundle\Entity
 */
#[ORM\MappedSuperclass]
#[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !', groups: ['persist','update'])]
abstract class MappSuperClassEntity implements WireEntityInterface
{
    use WireEntity;

    // public const ICON = [
    //     'ux' => 'tabler:question-mark',
    //     'fa' => 'fa-question'
    // ];
    // public const SERIALIZATION_PROPS = ['id','euid','classname','shortname'];

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