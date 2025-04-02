<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Entity\interface\WirePhonelinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity(fields: ['name','parent'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
abstract class WirePhonelink extends WireRelink implements WirePhonelinkInterface
{

    public const ICON = [
        'ux' => 'tabler:phone',
        'fa' => 'fa-phone'
    ];

    public const RELINK_TYPE = 'PHONE';

    // #[Gedmo\SortableGroup]
    // protected WireItemInterface & TraitRelinkableInterface $parent;

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