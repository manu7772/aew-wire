<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;
use Gedmo\Sortable\Sortable;

interface WireRelinkInterface extends WireEntityInterface, TraitDatetimedInterface, TraitUnamedInterface, Sortable, TranslationEntityInterface, TraitCategorizedInterface
{
    public function getALink(?int $referenceType = null): ?string;
    public function isUrl(): bool;
    public function isRoute(): bool;
    public function isRs(): bool;
    public function isAddress(): bool;
    public function isEmail(): bool;
    public function isPhone(): bool;
    public function getRelinkType(): ?string;
    public function getRelinkTypeChoices(): array;
    public function getMainlink(): ?string;
    public function setMainlink(?string $mainlink): static;
    public function isPrefered(): bool;
    public function setPrefered(bool $prefered): static;
    public function getParams(): ?array;
    public function setParams(?array $params): static;
    public function getTargetChoices(): array;
    public function getTarget(): ?string;
    public function getLogicTarget(): ?string;
    public function setTarget(?string $target): static;
    public function setTurboenabled(bool $turboenabled = true): static;
    public function isTurboenabled(): bool;
    public function getName(): ?string;
    public function setName(string $name): static;
    public function getLinktitle(): ?string;
    public function setLinktitle(?string $linktitle): static;
    // public function getParent(): TraitRelinkableInterface;
    // public function setParent(TraitRelinkableInterface $parent): static;
    public function getSlug(): string;
    public function getTranslations(): Collection;
    public function addTranslation(WireTranslationInterface $t): static;
    public function setOwnereuid(TraitRelinkableInterface $owner): static;
    public function getOwnereuid(): string;
}