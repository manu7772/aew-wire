<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Entity\IdGenerator\UnameIdGenerator;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Repository\UnameRepository;
use Aequation\WireBundle\Service\interface\UnameServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\UuidV7 as Uuid;
// PHP
use Exception;

#[ORM\Entity(repositoryClass: UnameRepository::class)]
#[UniqueEntity('uname', message: 'Ce uname {{ value }} est déjà utilisé !')]
#[UniqueEntity('euidofentity', message: 'Cet euid-of-entity {{ value }} est déjà utilisé !')]
#[ClassCustomService(UnameServiceInterface::class)]
class Uname extends MappSuperClassEntity implements UnameInterface
{

    public const ICON = "tabler:fingerprint";
    public const FA_ICON = "fingerprint";
    public const UNAME_PATTERN = '#^[\\w_-\\|\\.\\\\]{3,128}$#';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Serializer\Groups(['index'])]
    protected ?Uuid $id = null;

    #[ORM\Column(length: 255, updatable: false)]
    #[Assert\Length(min: 3, minMessage: 'Uname doit contenir au moins {{ min }} lettres')]
    #[Assert\Regex(Uname::UNAME_PATTERN)]
    protected string $uname;

    #[ORM\Column(length: 255, updatable: false)]
    #[Assert\NotNull]
    protected string $euidofentity;

    #[Serializer\Ignore]
    public readonly WireEntityInterface $entity;

    /**
     * get self as string
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->uname ?? parent::__toString();
    }

    /**
     * is valid uname
     * 
     * @param string $uname
     * @return bool
     */
    public static function isValidUname(
        string $uname
    ): bool
    {
        return preg_match(static::UNAME_PATTERN, $uname);
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
        string $uname = null
    ): static
    {
        if(!isset($this->entity)) {
            $this->entity = $entity;
        } else if($this->entity !== $entity) {
            throw new Exception(vsprintf("Error %s line %d:%s- Can not set another entity!", [__METHOD__, __LINE__, PHP_EOL]));
        }
        if(!empty($uname) || !isset($this->uname)) {
            if(empty($uname)) $uname = $this->entity->getEuid();
            $this->setUname($uname);
        }
        $this->euidofentity = $this->entity->getEuid();
        return $this;
    }

    /**
     * get uname
     * 
     * @return string
     */
    public function getUname(): string
    {
        return $this->uname;
    }

    /**
     * set uname
     * 
     * @param string $uname
     * @return static
     */
    public function setUname(string $uname): static
    {
        if(!static::isValidUname($uname)) throw new Exception(vsprintf('Error %s line %d:%s- Uname %s is invalid!', [__METHOD__, __LINE__, PHP_EOL, json_encode($uname)]));
        if(!$this->_estatus->isPersisted() || !static::isValidUname($this->uname)) {
            $this->uname = $uname;
        } else if($this->_estatus->appWire->isDev()) {
            // Can not change Uname, except if is invalid
            throw new Exception(vsprintf('Error %s line %d:%s- Uname can not be changed in persisted entity (%s named "%s")!', [__METHOD__, __LINE__, PHP_EOL, $this->entity->getClassname(), $this->entity->__toString()]));
        }
        return $this;
    }

    /**
     * get euidofentity
     * 
     * @return string
     */
    // #[Serializer\Ignore]
    public function getEuidofentity(): ?string
    {
        return $this->euidofentity;
    }

}
