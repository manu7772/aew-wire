<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitCategorizedInterface;
use Aequation\WireBundle\Entity\interface\WireCategoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
// Symfony
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
// PHP
use Exception;

trait Categorized
{

    /**
     * @var Collection<int, WireCategoryInterface>
     */
    #[ORM\ManyToMany(targetEntity: WireCategoryInterface::class)]
    protected Collection $categorys;


    public function __construct_categorized(): void
    {
        if(!($this instanceof TraitCategorizedInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitCategorizedInterface::class]));
        $this->categorys = new ArrayCollection();
    }


    public function getCategorys(): Collection
    {
        return $this->categorys;
    }

    public function setCategorys(Collection $categorys): static
    {
        $this->categorys->clear();
        foreach ($categorys as $category) {
            $this->addCategory($category);
        }
        return $this;
    }

    public function addCategory(WireCategoryInterface $category): static
    {
        if(!is_a($this, $category->getType())) {
            throw new Exception(vsprintf('Error %s line %d: category type "%s" is not compatible with entity type "%s"', [__METHOD__, __LINE__, $category->getType(), get_class($this)]));
        }
        if (!$this->categorys->contains($category)) {
            $this->categorys->add($category);
        }
        return $this;
    }

    public function removeCategory(WireCategoryInterface $category): static
    {
        if ($this->categorys->contains($category)) {
            $this->categorys->removeElement($category);
        }
        return $this;
    }

    public function hasCategory(WireCategoryInterface $category): bool
    {
        return $this->categorys->contains($category);
    }


}