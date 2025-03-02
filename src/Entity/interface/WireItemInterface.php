<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;
use Gedmo\Sortable\Sortable;

interface WireItemInterface extends WireEntityInterface, Sortable, SluggableInterface, TranslationEntityInterface, TraitEnabledInterface, TraitDatetimedInterface, TraitUnamedInterface
{
    public function getName(): ?string;
    public function setName(string $name): static;
    public function addParent(WireEcollectionInterface $parent): static;
    public function getParents(): Collection;
    public function hasParent(WireEcollectionInterface $parent): bool;
    public function removeParent(WireEcollectionInterface $parent): static;
    public function removeParents(): static;

} 