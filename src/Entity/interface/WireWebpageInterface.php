<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;
use Twig\Markup;

interface WireWebpageInterface extends WireItemInterface, TraitPreferedInterface
{

    public function getMainmenu(): WireMenuInterface;
    public function setMainmenu(WireMenuInterface $mainmenu): static;
    public function getSections(): Collection;
    public function getWebsections(?string $type = null): Collection;
    public function getWebsection(string $type): ?WireWebsectionInterface;
    public function setWebsections(Collection $sections): static;
    public function hasWebsection(WireWebsectionInterface $section): bool;
    public function addWebsection(WireWebsectionInterface $section): bool;
    public function removeWebsection(WireWebsectionInterface $section): bool;
    public function removeWebsections(): static;
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