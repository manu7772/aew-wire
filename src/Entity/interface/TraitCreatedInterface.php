<?php
namespace Aequation\WireBundle\Entity\interface;

// PHP

use Aequation\WireBundle\Service\interface\TimezoneInterface;
use DateTimeImmutable;
use DateTimeZone;

interface TraitCreatedInterface extends TraitInterface, TimezoneInterface
{
    public function __construct_created(): void;
    public function __clone_created(): void;
    public function getUpdatedAt(): ?DateTimeImmutable;
    public function updateUpdatedAt(): static;
    public function setUpdatedAt(): static;
    public function getCreatedAt(): ?DateTimeImmutable;
    public function updateCreatedAt(): static;
    public function setCreatedAt(): static;
    // public function getDateTimezone(): ?DateTimeZone;
    // public function getTimezone(): ?string;
    // public function setTimezone(string $timezone): static;
}
