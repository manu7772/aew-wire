<?php
namespace Aequation\WireBundle\Entity\interface;

use Twig\Markup;

interface TraitWebpageableInterface extends WireEntityInterface
{

    public function __construct_webpageable(): void;
    public static function getDefaultWebpageUname(): ?string;
    public function isWebpageRequired(): bool;
    public function setWebpage(?WireWebpageInterface $pageweb = null): static;
    public function getWebpage(): ?WireWebpageInterface;
    public function hasWebpage(): bool;
    // Attributes for webpage
    public function getTitle(): ?string;
    public function setTitle(?string $title): static;
    public function getContent(): ?array;
    public function setContent(?array $content): static;
    public function getContentToString(string $join = "\n"): ?string;
    public function getContentToHtml(string $join = "\n"): ?Markup;

}