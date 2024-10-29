<?php
namespace Aequation\WireBundle\Entity\interface;

// PHP
use DateTimeInterface;

interface WireArticleInterface extends WireItemInterface, TraitSlugInterface, TraitScreenableInterface
{

    public function getTitle(): ?string;
    public function setTitle(?string $title): static;
    public function getContent(): array;
    public function setContent(array $content): static;
    public function getStart(): ?DateTimeInterface;
    public function setStart(?DateTimeInterface $start): static;
    public function getEnd(): ?DateTimeInterface;
    public function setEnd(?DateTimeInterface $end): static;

}