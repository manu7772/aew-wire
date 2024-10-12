<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireUnameInterface;
use Aequation\WireBundle\Repository\UnameRepository;
use Aequation\WireBundle\Service\interface\UnameServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
// PHP
use Exception;

#[ORM\Entity(repositoryClass: UnameRepository::class)]
#[UniqueEntity('uname', message: 'Ce uname {{ value }} est déjà utilisé !')]
#[UniqueEntity('euidofentity', message: 'Cet euid-of-entity {{ value }} est déjà utilisé !')]
#[ClassCustomService(UnameServiceInterface::class)]
final class Uname extends MappSuperClassEntity implements UnameInterface
{

    public const ICON = "tabler:fingerprint";
    public const FA_ICON = "fingerprint";
    public const UNAME_PATTERN = '/^[\\w_-]{3,}$/';

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: 3, minMessage: 'Uname doit contenir au moins {{ min }} lettres')]
    #[Assert\Regex(Uname::UNAME_PATTERN)]
    private string $uname;

    #[ORM\Column(length: 255, updatable: false)]
    #[Assert\NotNull]
    private string $euidofentity;

    #[Serializer\Ignore]
    public readonly WireEntityInterface $entity;


    public function __toString(): string
    {
        return $this->uname ?? parent::__toString();
    }

    public static function isValidUname(
        string $uname
    ): bool
    {
        return preg_match(static::UNAME_PATTERN, $uname);
    }

    public function attributeEntity(
        TraitUnamedInterface $entity,
        string $uname = null
    ): static
    {
        if(!isset($this->entity)) {
            $this->entity = $entity;
        }
        if(!empty($uname) || !isset($this->uname)) {
            if(empty($uname)) $uname = $this->entity->getEuid();
            $this->setUname($uname);
        }
        $this->setEuidofentity($this->entity->getEuid());
        return $this;
    }

    public function getUname(): ?string
    {
        return $this->uname ?? null;
    }

    private function setUname(string $uname): static
    {
        if(!static::isValidUname($uname)) throw new Exception(vsprintf('Error %s line %d: uname %s is invalid!', [__METHOD__, __LINE__, json_encode($uname)]));
        $this->uname = $uname;
        return $this;
    }

    #[Serializer\Ignore]
    public function getEuidofentity(): ?string
    {
        return $this->euidofentity;
    }

    private function setEuidofentity(string $euidofentity): static
    {
        $this->euidofentity = $euidofentity;
        return $this;
    }
}
