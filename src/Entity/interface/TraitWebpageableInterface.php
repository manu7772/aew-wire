<?php
namespace Aequation\WireBundle\Entity\interface;

interface TraitWebpageableInterface
{

    public function __construct_webpageable(): void;
    public function isWebpageRequired(): bool;
    public function setWebpage(?WireWebpageInterface $pageweb = null): static;
    public function getWebpage(): ?WireWebpageInterface;
    public function hasWebpage(): bool;
    // Attributes for webpage
    public function updateTitles(): void;
    public function getTitle(): ?string;
    public function setTitle(string $title): static;
    public function getLinktitle(): ?string;
    public function setLinktitle(string $linktitle): static;
    public function getContent(): TextContentsInterface;
    public function setContent(TextContentsInterface $content): static;

}