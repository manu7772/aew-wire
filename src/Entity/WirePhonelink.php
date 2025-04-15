<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Entity\interface\WirePhonelinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['name','ownereuid'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
abstract class WirePhonelink extends WireRelink implements WirePhonelinkInterface
{

    public const ICON = [
        'ux' => 'tabler:phone',
        'fa' => 'fa-phone'
    ];
    public const RELINK_TYPE = 'PHONE';


    #[Assert\NotNull(message: 'Le numéro de téléphone est obligatoire', groups: ['persist','update'])]
    #[Assert\Regex(pattern: '/^\+?[0-9\s]+/', message: 'Le numéro de téléphone doit contenir uniquement des chiffres (et le caractère + en début, optionellement)', groups: ['persist','update'])]
    protected ?string $mainlink = null;

    public function setPhone(string $phone): static
    {
        $this->mainlink = preg_replace('/[^0-9\+]/', '', trim($phone));
        return $this;
    }

    public function getPhone(): string
    {
        return $this->mainlink;
    }

}