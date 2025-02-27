<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Service\interface\TimezoneInterface;
// PHP
use DateTimeImmutable;
use DateTimeZone;

interface TraitDatetimedInterface extends TraitInterface, TimezoneInterface
{
    public function __construct_datetimed(): void;
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
