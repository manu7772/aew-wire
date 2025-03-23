<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Entity\interface\WireEmailinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity(fields: ['name','itemowner'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
abstract class WireEmailink extends WireRelink implements WireEmailinkInterface
{

    public const ICON = 'tabler:mail';
    public const FA_ICON = 'envolope';

    public const RELINK_TYPE = 'EMAIL';

    // #[Gedmo\SortableGroup]
    // protected WireItemInterface & TraitRelinkableInterface $itemowner;

    // #[Gedmo\SortablePosition]
    // protected int $position;


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