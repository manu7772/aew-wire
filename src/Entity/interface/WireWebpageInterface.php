<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;

interface WireWebpageInterface extends WireEcollectionInterface, TraitPreferedInterface
{
    public function getSections(): Collection;
    public function setSections(iterable $sections): static;
    public function addSection($section): static;
    public function removeSection($section): static;
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