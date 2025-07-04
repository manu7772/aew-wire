<?php
namespace Aequation\WireBundle\Component\interface;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
// PHP
use Stringable;

interface WireClassMetadataCollectionInterface extends Collection, Selectable, Stringable
{
    public function isValid(): bool;
    public function mapSingleValue(string $field): array;
    public function getInfo(): array;
}