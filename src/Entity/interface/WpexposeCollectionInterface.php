<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Component\interface\TypedCollectionInterface;
use JsonSerializable;

interface WpexposeCollectionInterface extends WireEmbeddedInterface, TypedCollectionInterface, JsonSerializable
{
    public function isValid(): bool;
    public function regularize(): void;
    public function filterByInterface(string|array $interfaces, ?bool $plural = null): WpexposeCollectionInterface;
    public function isAvailableFor(object|string $classname, ?bool $plural = null): bool;
}
