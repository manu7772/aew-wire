<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireAddresslinkInterface;
use Aequation\WireBundle\Entity\interface\WireEmailinkInterface;
use Aequation\WireBundle\Entity\interface\WirePhonelinkInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
use Aequation\WireBundle\Entity\interface\WireUrlinkInterface;
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

    public function getAddresses(): Collection
    {
        return $this->relinks->filter(fn($relink) => $relink instanceof WireAddresslinkInterface);
    }

    public function getPhones(): Collection
    {
        return $this->relinks->filter(fn($relink) => $relink instanceof WirePhonelinkInterface);
    }

    public function getEmails(): Collection
    {
        return $this->relinks->filter(fn($relink) => $relink instanceof WireEmailinkInterface);
    }

    public function getUrls(): Collection
    {
        return $this->relinks->filter(fn($relink) => $relink instanceof WireUrlinkInterface);
    }


}