<?php

namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Entity\IdGenerator\UnameIdGenerator;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Repository\UnameRepository;
use Aequation\WireBundle\Service\interface\UnameServiceInterface;
use Aequation\WireBundle\Tools\Encoders;
use Doctrine\DBAL\Types\Types;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\UuidV7 as Uuid;
// PHP
use Exception;

#[ORM\Entity(repositoryClass: UnameRepository::class)]
#[ORM\Table(name: '`u_name`')]
// #[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !', groups: ['persist','update'])]
#[UniqueEntity('entityEuid', message: 'Cette entité (euid: {{ value }}) est déjà utilisé !', groups: ['persist','update'])]
#[ClassCustomService(UnameServiceInterface::class)]
class Uname extends BaseMappSuperClassEntity implements UnameInterface
{

    public const ICON = [
        'ux' => 'tabler:fingerprint',
        'fa' => 'fa-fingerprint'
    ];
    public const RESERVED_UNAMES = ['uname', 'id', 'euid', 'entityEuid', 'entity', 'entityClassname', 'entityShortname', 'entityEuid', 'entityId', 'entityUname', 'entityUnameId', 'entityUnameEuid'];

    #[ORM\Id]
    #[ORM\Column(updatable: false, type: Types::STRING, unique: true, length: 255)]
    #[Assert\Length(min: 3, minMessage: 'Uname doit contenir au moins {{ min }} lettres', groups: ['persist','update'])]
    #[Assert\Regex(pattern: Encoders::UNAME_SCHEMA, groups: ['persist','update'])]
    protected ?string $id = null;

    #[ORM\Column(updatable: false, unique: true, length: 255)]
    #[Assert\NotNull(groups: ['persist','update'])]
    #[Assert\Regex(pattern: Encoders::EUID_SCHEMA, groups: ['persist','update'])]
    protected string $entityEuid;

    public readonly ?WireEntityInterface $entity;

    /**
     * get self as string
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUname();
    }

    #[Assert\IsTrue(groups: ['persist','update'])]
    public function isValid(): bool
    {
        return Encoders::isUnameFormatValid($this->id);
    }

    /**
     * attribute entity
     * 
     * @param TraitUnamedInterface $entity
     * @param string|null $uname
     * @return static
     */
    public function attributeEntity(
        TraitUnamedInterface $entity,
        ?string $uname = null
    ): static {
        $this->entity ??= $entity;
        if ($this->entity !== $entity) {
            throw new Exception(vsprintf("Error %s line %d:%s- Can not set another entity!", [__METHOD__, __LINE__, PHP_EOL]));
        }
        if (!empty($uname) || empty($this->id)) {
            // if(!Encoders::isEuidFormatValid($uname) && !empty($uname)) dd($this->id, $uname, $this->getSelfState()->isNew(), Encoders::isUnameFormatValid($uname), !in_array(strtolower($uname), static::RESERVED_UNAMES));
            if (empty($uname)) $uname = $this->entity->getEuid();
            $this->setUname($uname);
        }
        $this->entityEuid = $this->entity->getEuid();
        return $this;
    }

    /**
     * get uname
     * 
     * @return string
     */
    public function getUname(): string
    {
        return (string)$this->id;
    }

    /**
     * set uname
     * 
     * @param string $uname
     * @return static
     */
    public function setUname(string $uname): static
    {
        if(
            $this->getSelfState()->isNew()
            && Encoders::isUnameFormatValid($uname)
            && !in_array(strtolower($uname), static::RESERVED_UNAMES)
        ) {
            $this->id = $uname;
        } else if($this->getEmbededStatus()->isDevOrSadmin()) {
            if(!$this->getSelfState()->isNew() && $uname !== $this->id) {
                // Can not update Uname if already set in database
                throw new Exception(vsprintf('Error %s line %d: Cant not update Uname as %s: Uname is already set with value %s!', [__METHOD__, __LINE__, json_encode($uname), json_encode($this->id)]));
            }
            if(!Encoders::isUnameFormatValid($uname)) {
                throw new Exception(vsprintf('Error %s line %d: Uname "%s" is not valid!', [__METHOD__, __LINE__, $uname]));
            }
            if(in_array(strtolower($uname), static::RESERVED_UNAMES)) {
                throw new Exception(vsprintf('Error %s line %d: Uname "%s" is reserved!', [__METHOD__, __LINE__, $uname]));
            }
        }
        // if(!Encoders::isEuidFormatValid($uname)) dd($uname, $this->getSelfState()->isNew(), Encoders::isUnameFormatValid($uname), !in_array(strtolower($uname), static::RESERVED_UNAMES));
        return $this;
    }

    /**
     * get entityEuid
     * 
     * @return string
     */
    public function getEntityEuid(): ?string
    {
        return $this->entityEuid ?? null;
    }

    public function getEntity(): ?TraitUnamedInterface
    {
        return $this->entity ??= $this->getEmbededStatus()?->getWireEm()->findByEuid($this->entityEuid) ?: null;
    }
}
