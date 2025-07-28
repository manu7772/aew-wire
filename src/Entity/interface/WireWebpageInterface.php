<?php
namespace Aequation\WireBundle\Entity\interface;

// Symfony
use Doctrine\Common\Collections\Collection;
use Aequation\WireBundle\Entity\WireWebpageWebsectionCollection;

interface WireWebpageInterface extends WireItemInterface, TraitPreferedInterface
{

    public function getMainmenu(): ?WireMenuInterface;
    public function setMainmenu(?WireMenuInterface $mainmenu): static;
    public function initTempSections(): void;
    public function findTempSection(WireWebsectionInterface|WireWebpageWebsectionCollection $section): ?WireWebpageWebsectionCollection;
    public function getSections(?string $type = null): Collection;
    public function setSections(iterable $sections): static;
    public function getSection(string $type): ?WireWebsectionInterface;
    public function addSection(WireWebsectionInterface|WireWebpageWebsectionCollection $section): bool;
    public function hasSection(WireWebsectionInterface|WireWebpageWebsectionCollection $section): bool;
    public function removeSection(WireWebsectionInterface|WireWebpageWebsectionCollection $section): bool;
    public function removeSections(): static;
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

}