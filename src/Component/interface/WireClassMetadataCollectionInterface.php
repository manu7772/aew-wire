<?php
namespace Aequation\WireBundle\Component\interface;

interface WireClassMetadataCollectionInterface extends TypedCollectionInterface
{
    public function isValid(): bool;
    public function mapSingleValue(string $field): array;
    public function getInfo(): array;
}