<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;

interface WireWebpageInterface extends WireItemInterface, TraitPreferedInterface
{
    public function getMainmenu(): WireMenuInterface;
    public function setMainmenu(WireMenuInterface $mainmenu): static;
    public function getSections(): Collection;
    public function getSectionsByType(string $type): Collection;
    public function setSections(iterable $sections): static;
    public function hasSection(WireWebsectionInterface $section): bool;
    public function addSection(WireWebsectionInterface $section): bool;
    public function removeSection(WireWebsectionInterface $section): bool;
    public function removeSections(): static;
    public function getTwigfileName(): ?string;
    public function getTwigfile(): ?string;
    public function setTwigfile(string $twigfile): static;
    public function getTitle(): ?string;
    public function setTitle(?string $title): static;
    public function getLinktitle(): ?string;
    public function setLinktitle(?string $linktitle): static;
    public function updateLinkTitle(): static;
    public function getContent(): array;
    public function setContent(array $content): static;
}