<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Dto\interface\MenuItemComponentDtoInterface;
use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Entity\WireMenu;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
// PHP
use Traversable;
use ArrayIterator;
use Closure;

// #[Map(target: WireMenu::class)]
// #[Map(target: WireItem::class)]
class MenuItemComponentDto implements MenuItemComponentDtoInterface
{

    // protected array $elements = [];
    public readonly PropertyAccessorInterface $accessor;

    // Fields
    #[Map(if: 'strlen')]
    public ?string $name = null;
    #[Map(if: 'strlen')]
    public ?string $title = null;
    #[Map(if: 'strlen')]
    public ?string $linktitle = null;
    #[Map(if: 'is_bool')]
    public bool $enabled = true;
    #[Map(if: 'is_int')]
    public ?int $position = null;
    public mixed $webpage = null;
    #[Map(if: 'count')]
    public array $childs;

    public function __construct(array $childs = [])
    {
        $this->childs = $childs;
    }

    public function __toString(): string
    {
        return $this->title;
    }

    protected function getAccessor(): PropertyAccessorInterface
    {
        return $this->accessor ??= PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidPropertyPath()->getPropertyAccessor();
    }


    /** MenuShapeInteface */

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function getLinktitle(): string
    {
        return $this->linktitle ?? '';
    }

    public function getItems(): iterable
    {
        return $this->childs;
    }


    /** Menu */

    protected function createFrom(array $childs): MenuItemComponentDtoInterface
    {
        return new static($childs);
    }

    public function toArray(): array
    {
        return $this->childs;
    }

    public function first(): mixed
    {
        return reset($this->childs);
    }

    public function last(): mixed
    {
        return end($this->childs);
    }

    public function key(): int|string|null
    {
        return key($this->childs);
    }

    public function next(): mixed
    {
        return next($this->childs);
    }

    public function rewind(): void
    {

    }

    public function current(): mixed
    {
        return current($this->childs);
    }

    public function remove(mixed $key): mixed
    {
        if (! isset($this->childs[$key]) && ! array_key_exists($key, $this->childs)) {
            return null;
        }

        $removed = $this->childs[$key];
        unset($this->childs[$key]);

        return $removed;
    }

    public function removeElement(mixed $element): bool
    {
        $key = array_search($element, $this->childs, true);

        if ($key === false) {
            return false;
        }

        unset($this->childs[$key]);

        return true;
    }

    /**
     * Required by interface ArrayAccess.
     *
     * @param TKey $offset
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->containsKey($offset);
    }

    /**
     * Required by interface ArrayAccess.
     *
     * @param TKey $offset
     *
     * @return T|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Required by interface ArrayAccess.
     *
     * @param TKey|null $offset
     * @param T         $value
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->add($value);

            return;
        }

        $this->set($offset, $value);
    }

    /**
     * Required by interface ArrayAccess.
     *
     * @param TKey $offset
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    public function containsKey(mixed $key): bool
    {
        return isset($this->childs[$key]) || array_key_exists($key, $this->childs);
    }

    public function contains(mixed $element)
    {
        return in_array($element, $this->childs, true);
    }

    public function exists(Closure $p): bool
    {
        return array_any(
            $this->childs,
            static fn (mixed $element, mixed $key): bool => (bool) $p($key, $element),
        );
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-param TMaybeContained $element
     *
     * @return int|string|false
     * @phpstan-return (TMaybeContained is T ? TKey|false : false)
     *
     * @template TMaybeContained
     */
    public function indexOf($element): int|string|false
    {
        return array_search($element, $this->childs, true);
    }

    public function get(mixed $key): mixed
    {
        return $this->childs[$key] ?? null;
    }

    public function getKeys(): array
    {
        return array_keys($this->childs);
    }

    public function getValues(): array
    {
        return array_values($this->childs);
    }

    /**
     * {@inheritDoc}
     *
     * @return int<0, max>
     */
    public function count(): int
    {
        return count($this->childs);
    }

    public function set(mixed $key, mixed $value): void
    {
        $this->childs[$key] = $value;
    }

    /**
     * {@inheritDoc}
     *
     * This breaks assumptions about the template type, but it would
     * be a backwards-incompatible change to remove this method
     */
    public function add(mixed $element): void
    {
        $this->childs[] = $element;
    }

    public function isEmpty(): bool
    {
        return empty($this->childs);
    }

    /**
     * {@inheritDoc}
     *
     * @return Traversable<int|string, mixed>
     * @phpstan-return Traversable<TKey, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->childs);
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-param Closure(T):U $func
     *
     * @return static
     * @phpstan-return static<TKey, U>
     *
     * @phpstan-template U
     */
    public function map(Closure $func): MenuItemComponentDtoInterface
    {
        return $this->createFrom(array_map($func, $this->childs));
    }

    public function reduce(Closure $func, $initial = null)
    {
        return array_reduce($this->childs, $func, $initial);
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-param Closure(T, TKey):bool $p
     *
     * @return static
     * @phpstan-return static<TKey,T>
     */
    public function filter(Closure $p): MenuItemComponentDtoInterface
    {
        return $this->createFrom(array_filter($this->childs, $p, ARRAY_FILTER_USE_BOTH));
    }

    public function findFirst(Closure $p): mixed
    {
        return array_find(
            $this->childs,
            static fn (mixed $element, mixed $key): bool => (bool) $p($key, $element),
        );
    }

    public function forAll(Closure $p): bool
    {
        return array_all(
            $this->childs,
            static fn (mixed $element, mixed $key): bool => (bool) $p($key, $element),
        );
    }

    public function partition(Closure $p): array
    {
        $matches = $noMatches = [];

        foreach ($this->childs as $key => $element) {
            if ($p($key, $element)) {
                $matches[$key] = $element;
            } else {
                $noMatches[$key] = $element;
            }
        }

        return [$this->createFrom($matches), $this->createFrom($noMatches)];
    }

    public function clear(): void
    {
        $this->childs = [];
    }

    public function slice(int $offset, int|null $length = null): array
    {
        return array_slice($this->childs, $offset, $length, true);
    }

    public function sortBy(string $property, bool $asc = true): MenuItemComponentDtoInterface
    {
        usort($this->childs, function ($a, $b) use ($property, $asc) {
            $aValue = $asc ? $this->getAccessor()->getValue($b, $property) : $this->getAccessor()->getValue($a, $property);
            $bValue = $asc ? $this->getAccessor()->getValue($a, $property) : $this->getAccessor()->getValue($b, $property);
            return is_string($aValue)
                ? strcmp((string) $aValue, (string) $bValue) // String comparison
                : $aValue <=> $bValue; // Numeric comparison
        });
        return $this;
    }

    public function sortFn(Closure $callback): MenuItemComponentDtoInterface
    {
        usort($this->childs, $callback);
        return $this;
    }



}