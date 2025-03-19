<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\trait\Relinkable;
use Aequation\WireBundle\Entity\trait\Webpageable;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Gedmo\Mapping\Annotation as Gedmo;

abstract class WireFactory extends WireItem implements WireFactoryInterface, TraitRelinkableInterface
{

    use Webpageable, Relinkable;

    public const ICON = [
        'ux' => 'tabler:building-factory-2',
        'fa' => 'fa-solid fa-industry'
    ];

    #[ORM\Column(nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $functionality = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $description = null;


    public function getFunctionality(): ?string
    {
        return $this->functionality;
    }

    public function setFunctionality(?string $functionality = null): static
    {
        $this->functionality = $functionality;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description = null): static
    {
        $this->description = $description;
        return $this;
    }

}
