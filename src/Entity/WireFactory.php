<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\TraitCategorizedInterface;
use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\trait\Categorized;
use Aequation\WireBundle\Entity\trait\Relinkable;
use Aequation\WireBundle\Entity\trait\Webpageable;
use Doctrine\Common\Collections\Collection;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Gedmo\Mapping\Annotation as Gedmo;

abstract class WireFactory extends WireItem implements WireFactoryInterface, TraitRelinkableInterface, TraitCategorizedInterface
{

    use Webpageable, Relinkable, Categorized;

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

    /**
     * @var Collection<int, WireUserInterface>
     */
    #[ORM\ManyToMany(targetEntity: WireUserInterface::class, inversedBy: 'factorys')]
    protected Collection $associates;

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

    public function getAssociates(): Collection
    {
        return $this->associates;
    }

    public function addAssociate(WireUserInterface $associate): static
    {
        if (!$this->associates->contains($associate)) {
            $this->associates->add($associate);
        }
        if(!$associate->hasFactory($this)) {
            $associate->addFactory($this);
        }
        return $this;
    }

    public function removeAssociate(WireUserInterface $associate): static
    {
        $this->associates->removeElement($associate);
        if($associate->hasFactory($this)) {
            $associate->removeFactory($this);
        }
        return $this;
    }

    public function hasAssociate(WireUserInterface $associate): bool
    {
        return $this->associates->contains($associate);
    }


}
