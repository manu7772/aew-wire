<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
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
abstract class BaseMappSuperClassEntity implements BaseEntityInterface
{
    use WireEntity;

    public const ICON = [
        'ux' => 'tabler:file',
        'fa' => 'fa-file'
        // Add other types and their corresponding icons here
    ];
    public const SERIALIZATION_PROPS = ['id'];
    public const DO_EMBED_STATUS_EVENTS = [];
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