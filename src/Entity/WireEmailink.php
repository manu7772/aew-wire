<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Entity\interface\WireEmailinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['name','ownereuid'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
abstract class WireEmailink extends WireRelink implements WireEmailinkInterface
{

    public const ICON = [
        'ux' => 'tabler:mail',
        'fa' => 'fa-envelope'
    ];
    public const RELINK_TYPE = 'EMAIL';


    #[Assert\NotNull(message: 'L\'email est obligatoire', groups: ['persist','update'])]
    protected ?string $mainlink = null;

    public function setEmail(string $email): static
    {
        $this->mainlink = $email;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->mainlink;
    }

}