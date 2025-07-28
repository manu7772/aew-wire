<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Component\TwigfileMetadata;

interface WireWebsectionInterface extends WireEntityInterface, TraitEnabledInterface, TraitUnamedInterface, TraitPreferedInterface
{
    public function setTempWebpage(?WireWebpageInterface $webpage): static;
    public function getTempWebpage(): ?WireWebpageInterface;
    public function getMainmenu(bool $useTempWebpage = false): ?WireMenuInterface;
    public function setMainmenu(?WireMenuInterface $mainmenu): static;
    public function getTwigfileChoices(): array;
    public function getTwigfileName(): ?string;
    public function getTwigfile(): ?string;
    public function setTwigfile(string $twigfile): static;
    public function getTwigfileMetadata(): TwigfileMetadata;
    public function getSectiontype(): string;
    public function setDefaultSectiontype(): static;
    public function setSectiontype(string $sectiontype): static;

}