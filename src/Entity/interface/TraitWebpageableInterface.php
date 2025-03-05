<?php
namespace Aequation\WireBundle\Entity\interface;


interface TraitWebpageableInterface extends TraitEntityInterface
{

    public function __construct_webpageable(): void;
    public function isWebpageRequired(): bool;
    public function setWebpage(?WireWebpageInterface $pageweb = null): static;
    public function getWebpage(): ?WireWebpageInterface;
    // Attributes for webpage
    public function getTitle(): ?string;
    public function setTitle(?string $title): static;
    public function getContent(): array;
    public function setContent(array $content): static;

}