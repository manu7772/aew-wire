<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Entity\interface\WirePhonelinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity(fields: ['name','itemowner'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
abstract class WirePhonelink extends WireRelink implements WirePhonelinkInterface
{

    public const ICON = 'tabler:phone';
    public const FA_ICON = 'phone';

    public const RELINK_TYPE = 'PHONE';

    // #[Gedmo\SortableGroup]
    // protected WireItemInterface & TraitRelinkableInterface $itemowner;

    // #[Gedmo\SortablePosition]
    // protected int $position;


    public function setPhone(string $phone): static
    {
        $this->mainlink = preg_replace('/[^0-9\+]/', '', $phone);
        return $this;
    }

    public function getPhone(): string
    {
        return $this->mainlink;
    }

}