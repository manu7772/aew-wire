<?php
namespace Aequation\WireBundle\Component\interface;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
// PHP
use Closure;
use Stringable;

interface TypedCollectionInterface extends Collection, Selectable, Stringable
{
    // public function containsKey(string|int $key): bool;
    public function mapSingleValue(string $field): array;
    public function sortBy(string $property, bool $asc = true): TypedCollectionInterface;
    public function sortFn(Closure $callback): TypedCollectionInterface;
}