<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;
use Gedmo\Sortable\Sortable;

interface WireItemInterface extends WireEntityInterface, BetweenManyChildInterface, Sortable, SluggableInterface, TranslationEntityInterface, TraitEnabledInterface, TraitDatetimedInterface, TraitUnamedInterface
{
    public function getName(): ?string;
    public function setName(string $name): static;
    public function getTempParent(): ?WireEcollectionInterface;
    public function setTempParent(?WireEcollectionInterface $tempParent = null): static;
    public function getPosition(?WireEcollectionInterface $parent = null): int|false;
    public function getMainparent(): ?WireEcollectionInterface;
    public function setMainparent(WireEcollectionInterface $mainparent): bool;
    public function removeMainparent(): bool;
    public function attributeDefaultMainparent(): static;
    public function addParent(WireEcollectionInterface $parent): bool;
    public function getParents(): Collection;
    public function hasParent(WireEcollectionInterface $parent): bool;
    public function removeParent(WireEcollectionInterface $parent): bool;
    public function removeParents(): bool;
} 