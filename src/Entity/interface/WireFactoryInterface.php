<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;

interface WireFactoryInterface extends WireItemInterface, TraitWebpageableInterface, TraitRelinkableInterface, TraitCategorizedInterface
{
    public function getAssociates(): Collection;
    public function addAssociate(WireUserInterface $associate): static;
    public function removeAssociate(WireUserInterface $associate): static;
    public function hasAssociate(WireUserInterface $associate): bool;
    public function getFunctionality(): ?string;
    public function setFunctionality(?string $functionality = null): static;
    public function getDescription(): ?string;
    public function setDescription(?string $description = null): static;
}