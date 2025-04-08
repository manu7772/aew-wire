<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Service\interface\TimezoneInterface;
// Symfony
use Doctrine\Common\Collections\Collection;
// PHP
use DateTimeZone;

interface WireLanguageInterface extends WireEntityInterface, TimezoneInterface, TraitUnamedInterface, TraitEnabledInterface, TraitPreferedInterface, TranslationEntityInterface
{
    public function getLocale(): ?string;
    public function setLocale(string $locale): static;
    public function getLocaleChoices(): array;
    public function getDateTimezone(): ?DateTimeZone;
    public function getTimezone(): string;
    public function setTimezone(string $timezone): static;
    public function getName(): string;
    public function setName(string $name): static;
    public function getDescription(): ?string;
    public function setDescription(?string $description): static;
    public function getTranslations(): Collection;
    public function addTranslation(WireTranslationInterface $t);
}