<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;

interface TraitRelinkableInterface extends TraitEntityInterface
{
    public function getRelinks(): Collection;
    public function addRelink(WireRelinkInterface $relink): static;
    public function hasRelink(WireRelinkInterface $relink): bool;
    public function removeRelink(WireRelinkInterface $relink): static;

}