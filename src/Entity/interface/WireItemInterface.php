<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Entity\WireEcollection;
// Symfony
use Doctrine\Common\Collections\Collection;

interface WireItemInterface extends WireEntityInterface
{

    public function getName(): ?string;
    public function setName(string $name): static;
    public function addParent(WireEcollection $parent): static;
    public function getParents(): Collection;
    public function hasParent(WireEcollection $parent): bool;
    public function removeParent(WireEcollection $parent): static;

}