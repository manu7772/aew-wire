<?php
namespace Aequation\WireBundle\Entity\interface;

// PHP
use DateTimeImmutable;
use DateTimeZone;

interface TraitCreatedInterface extends TraitInterface
{
    public function __construct_created(): void;
    public function __clone_created(): void;
    public function getUpdatedAt(): ?DateTimeImmutable;
    public function updateUpdatedAt(): static;
    public function setUpdatedAt(): static;
    public function getCreatedAt(): ?DateTimeImmutable;
    public function updateCreatedAt(): static;
    public function setCreatedAt(): static;
    public function getDateTimezone(): ?DateTimeZone;
    public function getTimezone(): ?string;
    public function setTimezone(string $timezone): static;
}
