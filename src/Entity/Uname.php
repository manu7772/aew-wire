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
#[ORM\Table(name: '`uname`')]
#[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !')]
#[UniqueEntity('entityEuid', message: 'Cette entité (euid: {{ value }}) est déjà utilisé !')]
#[ClassCustomService(UnameServiceInterface::class)]
class Uname extends MappSuperClassEntity implements UnameInterface
{

    public const ICON = [
        'ux' => 'tabler:fingerprint',
        'fa' => 'fa-fingerprint'
    ];
    public const UNAME_PATTERN = '#^[\\w_-\\|\\.\\\\]{3,128}$#';

    #[ORM\Id]
    // #[ORM\GeneratedValue(strategy: "NONE")]
    #[ORM\Column(updatable: false, type: Types::STRING, unique: true)]
    #[Assert\Length(min: 3, minMessage: 'Uname doit contenir au moins {{ min }} lettres')]
    #[Assert\Regex(Uname::UNAME_PATTERN)]
    protected ?string $id = null;

    #[ORM\Column(updatable: false, unique: true)]
    #[Assert\NotNull]
    #[Assert\Regex(Encoders::EUID_SCHEMA)]
    protected string $entityEuid;

    public readonly WireEntityInterface $entity;

    /**
     * get self as string
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->id ? (string)$this->id : parent::__toString();
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
        ?string $uname = null
    ): static
    {
        if(!isset($this->entity)) {
            $this->entity = $entity;
        } else if($this->entity !== $entity) {
            throw new Exception(vsprintf("Error %s line %d:%s- Can not set another entity!", [__METHOD__, __LINE__, PHP_EOL]));
        }
        if(!empty($uname) || empty($this->id ?? null)) {
            if(empty($uname)) $uname = $this->entity->getEuid();
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
        return $this->id;
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
        $this->id = $uname;
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

}
