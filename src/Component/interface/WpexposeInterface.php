<?php
namespace Aequation\WireBundle\Component\interface;

// PHP
use JsonSerializable;
use Stringable;

interface WpexposeInterface extends Stringable, RepresentationInterface, JsonSerializable
{
    public function toArray(): array;
    public function isValid(): bool;
    public function getInterface(): ?string;
    public function setInterface(string $interface): static;
    public function isPlural(): bool;
    public function setPlural(bool $plural = true): static;
    public function setSingular(bool $singlular = true): static;
    public function isSingular(): bool;
    // Match methods
    public function isInterfaceOf(string|array|object $interfaces, ?bool $plural = null): bool;
    public function matchParams(...$params): bool;
    public function matchParam(string $param_name, mixed $value): bool;
}
