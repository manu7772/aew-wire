<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Entity\WireWebpageWebsectionCollection;
use Doctrine\Common\Collections\Collection;
use Twig\Markup;

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
    public function getTwigfile(): ?string;
    public function setTwigfile(string $twigfile): static;
    public function getTitle(): ?string;
    public function setTitle(?string $title): static;
    public function getLinktitle(): ?string;
    public function setLinktitle(?string $linktitle): static;
    public function updateLinkTitle(): static;
    public function getContent(): array;
    public function setContent(array $content): static;
    public function getContentToString(string $join = "\n"): ?string;
    public function getContentToHtml(string $join = "\n"): ?Markup;

}