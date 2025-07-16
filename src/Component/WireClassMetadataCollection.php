<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\WireClassMetadataCollectionInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
// PHP
use InvalidArgumentException;

class WireClassMetadataCollection extends TypedCollection implements WireClassMetadataCollectionInterface
{
    // /**
    //  * An array containing the entries of this collection.
    //  *
    //  * @phpstan-var array<TKey,T>
    //  * @var mixed[]
    //  */
    // private array $elements = [];

    /**
     * Initializes a new ArrayCollection.
     *
     * @phpstan-param array<TKey,T> $elements
     */
    public function __construct(array $elements = [])
    {
        foreach ($elements as $value) {
            /** @var WireClassMetadataInterface $value */
            $this->add($value);
        }
    }

    public function isValid(): bool
    {
        // Check if all elements are instances of WireClassMetadataInterface
        return $this->forAll(
            fn (mixed $key, WireClassMetadataInterface $element): bool => $element instanceof WireClassMetadataInterface && $key === $element->getName()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function set(string|int $key, mixed $value): void
    {
        if($value->getName() !== $key) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: key "%s" does not match value name "%s".', [__METHOD__, __LINE__, $key, $value->getName()]));
        }
        if(!($value instanceof WireClassMetadataInterface)) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: value must be an instance of %s, %s given.', [__METHOD__, __LINE__, WireClassMetadataInterface::class, is_object($value) ? get_class($value) : gettype($value)]));
        }
        if($this->containsKey($value->getName()) || $this->contains($value)) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: a class metadata with name "%s" already exists in this collection.', [__METHOD__, __LINE__, $value->getName()]));
        }
        parent::set($value->getName(), $value);
    }

    /**
     * {@inheritDoc}
     *
     * This breaks assumptions about the template type, but it would
     * be a backwards-incompatible change to remove this method
     */
    public function add(mixed $element): void
    {
        parent::set($element->getName(), $element);
    }

    public function getInfo(): array
    {
        $info = [];
        foreach ($this as $element) {
            /** @var WireClassMetadataInterface $element */
            $info[$element->getName()] = $element->getInfo();
        }
        return $info;
    }

}