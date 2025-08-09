<?php
namespace Aequation\WireBundle\Entity\interface;

// Symfony

use Aequation\WireBundle\Component\interface\WpexposeInterface;
use Doctrine\Common\Collections\Collection;
use Aequation\WireBundle\Entity\WireWebpageWebsectionCollection;

interface WireWebpageInterface extends WireItemInterface, TraitPreferedInterface, BetweenSortedParentInterface
{

    public function getMainmenu(): ?WireMenuInterface;
    public function setMainmenu(?WireMenuInterface $mainmenu): static;
    public function initTempSections(): void;
    public function findTempSection(WireWebsectionInterface|WireWebpageWebsectionCollection $section): ?WireWebpageWebsectionCollection;
    public function getSections(?string $type = null, bool $passBetweens = true): Collection;
    public function setSections(iterable $sections): static;
    public function getSection(string $type, bool $passBetween = true): ?WireWebsectionInterface;
    public function addSection(WireWebsectionInterface|WireWebpageWebsectionCollection $section): bool;
    public function hasSection(WireWebsectionInterface|WireWebpageWebsectionCollection $section): bool;
    public function removeSection(WireWebsectionInterface|WireWebpageWebsectionCollection $section): bool;
    public function removeSections(): static;
    public function getSectionPosition(WireWebsectionInterface $section): int|false;
    public function setSectionPosition(WireWebsectionInterface $section, int $position): bool;
    public function getTwigfileName(): ?string;
    public function getTwigfile(): ?TwigfileInterface;
    public function setTwigfile(TwigfileInterface $twigfile): static;
    public function getTitle(): ?string;
    public function setTitle(?string $title): static;
    public function getLinktitle(): ?string;
    public function setLinktitle(?string $linktitle): static;
    public function updateLinkTitle(): static;
    public function getContent(): TextContentsInterface;
    public function setContent(TextContentsInterface $content): static;
    // Exposes
    public function isAvailableForExpose(string|object $item, ?bool $plural = null): bool;
    public function getWpexposes(): WpexposeCollectionInterface;
    public function setWpexposes(WpexposeCollectionInterface $wpexposes): static;
    public function addWpexpose(...$params): static;
    public function hasWpexpose(...$params): bool;
    public function getWpexpose(...$params): ?WpexposeInterface;
    public function removeWpexpose(...$params): static;
    public function postLoad_wpexpose(): void;
}