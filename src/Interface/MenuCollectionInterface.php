<?php
namespace Aequation\WireBundle\Interface;

// PHP
use Closure;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use Stringable;

interface MenuCollectionInterface extends Stringable, ArrayAccess, Countable, IteratorAggregate
{
    public function contains(mixed $element);
    public function isEmpty();
    public function containsKey(string|int $key);
    public function get(string|int $key);
    public function getKeys();
    public function getValues();
    public function toArray();
    public function first();
    public function last();
    public function key();
    public function current();
    public function next();
    public function slice(int $offset, int|null $length = null);
    public function exists(Closure $p);
    public function filter(Closure $p);
    public function map(Closure $func);
    public function partition(Closure $p);
    public function forAll(Closure $p);
    public function indexOf(mixed $element);
    public function findFirst(Closure $p);
    public function reduce(Closure $func, mixed $initial = null);
}