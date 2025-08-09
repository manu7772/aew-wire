<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Component\Wpexpose;
use Aequation\WireBundle\Component\interface\WpexposeInterface;
use Aequation\WireBundle\Component\interface\TypedCollectionInterface;
use Aequation\WireBundle\Component\TypedCollection;
use Aequation\WireBundle\Entity\interface\WpexposeCollectionInterface;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\Mapping as ORM;
// PHP

#[ORM\Embeddable()]
class WpexposeCollection extends TypedCollection implements WpexposeCollectionInterface
{

    #[ORM\Column(type: 'json')]
    protected array $elements = [];

    public function __construct(...$elements)
    {
        foreach ($elements as $key => $value) {
            $cont = $value instanceof WpexposeInterface ? $value : new Wpexpose($value);
            $this->set($key, $cont);
        }
    }

    public function isValid(): bool
    {
        return !$this->exists(fn (int|string $key, mixed $wpexpose) => !($wpexpose instanceof WpexposeInterface) || !$wpexpose->isValid());
    }

    public function regularize(): void
    {
        // if(!$this->isValid()) {
            $this->elements = array_map(fn (mixed $data) => $data instanceof WpexposeInterface ? $data : new Wpexpose($data), $this->elements);
            // $this->elements = array_filter($this->elements, fn (WpexposeInterface $wpexpose) => $wpexpose->isValid());
            // dump($this->elements);
            foreach ($this->elements as $key => $value) {
                if(!($value instanceof WpexposeInterface)) {
                    dump(vsprintf('Data of %s is not a WpexposeInterface, but a %s%s', [Objects::toDebugString($value), Encoders::isJson($value) ? 'JSON ' : '', gettype($value)]));
                }
            }
        // }
        if(!$this->isValid()) {
            $message = vsprintf('Error %s line %d: WpexposeCollection is not valid, some elements are not WpexposeInterface', [__METHOD__, __LINE__]);
            dump($this->elements);
            throw new \InvalidArgumentException($message);
        }
    }

    public function __toString(): string
    {
        $strings = array_map(fn (WpexposeInterface $wpexpose) => $wpexpose->__toString(), $this->elements);
        return implode(', ', $strings);
    }

    public function toArray(): array
    {
        return array_map(fn (WpexposeInterface $wpexpose) => $wpexpose->toArray(), $this->elements);
    }

    public function jsonSerialize(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }

    protected function createFrom(array $elements): TypedCollectionInterface&WpexposeCollectionInterface
    {
        return new static(...$elements);
    }

    public function filterByInterface(string|array $interfaces, ?bool $plural = null): WpexposeCollectionInterface
    {
        return $this->filter(fn (WpexposeInterface $item) => $item->isInterfaceOf($interfaces, $plural));
    }

    public function isAvailableFor(object|string $item, ?bool $plural = null): bool
    {
        // dump($this);
        $this->regularize();
        return $this->exists(fn (int|string $key, WpexposeInterface $wpexpose) => $wpexpose->isInterfaceOf($item, $plural));
    }

    public function set(string|int $key, mixed $value): void
    {
        $value = Encoders::fromJson($value);
        if (!($value instanceof WpexposeInterface)) {
            $value = new Wpexpose($value);
        }
        if($value->isValid()) {
            parent::set($value->__toString(), $value);
        }
    }

    public function add(mixed $element): void
    {
        $element = Encoders::fromJson($element);
        if (!($element instanceof WpexposeInterface)) {
            $element = new Wpexpose($element);
        }
        if($element->isValid()) {
            parent::set($element->__toString(), $element);
        }
    }

}