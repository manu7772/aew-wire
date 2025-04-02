<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireAddresslinkInterface;
use Aequation\WireBundle\Entity\interface\WireEmailinkInterface;
use Aequation\WireBundle\Entity\interface\WirePhonelinkInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
use Aequation\WireBundle\Entity\interface\WireRslinkInterface;
use Aequation\WireBundle\Entity\interface\WireUrlinkInterface;
use Doctrine\Common\Collections\ArrayCollection;
// Symfony
use Doctrine\Common\Collections\Collection;
// PHP
use Exception;

trait Relinkable
{
    protected Collection $relinks;

    public function __construct_relinkable(): void
    {
        if(!($this instanceof TraitRelinkableInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitRelinkableInterface::class]));
        $this->relinks = new ArrayCollection();
    }

    public function getRelinks(): Collection
    {
        return $this->relinks->map(fn($relink) => $relink->getRelink());
    }

    public function addRelink(WireRelinkInterface $relink): bool
    {
        if(!$this->hasRelink($relink)) {
            $linkclass = 'Aequation\\WireBundle\\Entity\\Wire'.$this->getShortname().'RelinkCollection';
            $this->relinks->add(new $linkclass($this, $relink));
        }
        return $this->hasRelink($relink);
    }

    public function hasRelink(WireRelinkInterface $relink): bool
    {
        return $this->getRelinks()->contains($relink);
    }

    public function removeRelink(WireRelinkInterface $relink): bool
    {
        foreach ($this->relinks as $link) {
            if($link->getRelink() === $relink) {
                $this->relinks->removeElement($link);
                return true;
            }
        }
        return false;
    }

    // AddressLink

    public function getAddresses(): Collection
    {
        return $this->getRelinks()->filter(fn($relink) => $relink instanceof WireAddresslinkInterface);
    }

    public function setAddresses(iterable $relinks): static
    {
        foreach ($this->getAddresses() as $relink) {
            if(!$relinks->contains($relink)) {
                $this->removeRelink($relink);
            }
        }
        foreach ($relinks as $relink) {
            $this->addRelink($relink);
        }
        return $this;
    }

    public function addAddresse(WireAddresslinkInterface $relink): bool
    {
        return $this->addRelink($relink);
    }

    public function removeAddresse(WireAddresslinkInterface $relink): bool
    {
        return $this->removeRelink($relink);
    }

    // PhoneLink

    public function getPhones(): Collection
    {
        return $this->getRelinks()->filter(fn($relink) => $relink instanceof WirePhonelinkInterface);
    }

    public function setPhones(iterable $relinks): static
    {
        foreach ($this->getPhones() as $relink) {
            if(!$relinks->contains($relink)) {
                $this->removeRelink($relink);
            }
        }
        foreach ($relinks as $relink) {
            $this->addRelink($relink);
        }
        return $this;
    }

    public function addPhone(WirePhonelinkInterface $relink): bool
    {
        return $this->addRelink($relink);
    }

    public function removePhone(WirePhonelinkInterface $relink): bool
    {
        return $this->removeRelink($relink);
    }

    // EmaiLink

    public function getEmails(): Collection
    {
        return $this->getRelinks()->filter(fn($relink) => $relink instanceof WireEmailinkInterface);
    }

    public function setEmails(iterable $relinks): static
    {
        foreach ($this->getEmails() as $relink) {
            if(!$relinks->contains($relink)) {
                $this->removeRelink($relink);
            }
        }
        foreach ($relinks as $relink) {
            $this->addRelink($relink);
        }
        return $this;
    }

    public function addEmail(WireEmailinkInterface $relink): bool
    {
        return $this->addRelink($relink);
    }

    public function removeEmail(WireEmailinkInterface $relink): bool
    {
        return $this->removeRelink($relink);
    }

    // UrLink

    public function getUrls(): Collection
    {
        return $this->getRelinks()->filter(fn($relink) => $relink instanceof WireUrlinkInterface);
    }

    public function setUrls(iterable $relinks): static
    {
        foreach ($this->getUrls() as $relink) {
            if(!$relinks->contains($relink)) {
                $this->removeRelink($relink);
            }
        }
        foreach ($relinks as $relink) {
            $this->addRelink($relink);
        }
        return $this;
    }

    public function addUrl(WireUrlinkInterface $relink): bool
    {
        return $this->addRelink($relink);
    }

    public function removeUrl(WireUrlinkInterface $relink): bool
    {
        return $this->removeRelink($relink);
    }

    // RsLink

    public function getRs(): Collection
    {
        return $this->getRelinks()->filter(fn($relink) => $relink instanceof WireRslinkInterface);
    }

    public function setRs(iterable $relinks): static
    {
        foreach ($this->getRs() as $relink) {
            if(!$relinks->contains($relink)) {
                $this->removeRelink($relink);
            }
        }
        foreach ($relinks as $relink) {
            $this->addRelink($relink);
        }
        return $this;
    }

    public function addRs(WireRslinkInterface $relink): bool
    {
        return $this->addRelink($relink);
    }

    public function removeRs(WireRslinkInterface $relink): bool
    {
        return $this->removeRelink($relink);
    }


}