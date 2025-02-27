<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
use Doctrine\Common\Collections\Collection;

trait Relinkable
{

    public function getRelinks(): Collection
    {
        return $this->relinks;
    }

    public function addRelink(WireRelinkInterface $relink): static
    {
        if($this instanceof TraitRelinkableInterface) {
            if(!$this->relinks->contains($relink)) {
                $this->relinks->add($relink);
            }
            if($relink->getItemowner() !== $this) $relink->setItemowner($this);
        } else {
            $this->removeRelink($relink);
        }
        return $this;
    }

    public function hasRelink(WireRelinkInterface $relink): bool
    {
        return $this->relinks->contains($relink);
    }

    public function removeRelink(WireRelinkInterface $relink): static
    {
        $this->relinks->removeElement($relink);
        // if($relink->getItemowner() === $this) $relink->setItemowner(null);
        return $this;
    }


}