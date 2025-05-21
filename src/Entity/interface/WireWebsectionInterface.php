<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Component\TwigfileMetadata;

interface WireWebsectionInterface extends WireEntityInterface, TraitUnamedInterface
{
    public function getMainmenu(): WireMenuInterface;
    public function setMainmenu(WireMenuInterface $mainmenu): static;
    public function getTwigfileChoices(): array;
    public function getTwigfileName(): ?string;
    public function getTwigfile(): ?string;
    public function setTwigfile(string $twigfile): static;
    public function isPrefered(): bool;
    public function setPrefered(bool $prefered): static;
    public function getTwigfileMetadata(): TwigfileMetadata;
    public function getSectiontype(): string;
    public function setDefaultSectiontype(): static;
    public function setSectiontype(string $sectiontype): static;

}