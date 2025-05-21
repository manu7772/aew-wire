<?php
namespace Aequation\WireBundle\Attribute;

// PHP
use Exception;
use JsonSerializable;
use ReflectionClass;
use Serializable;

abstract class BaseClassAttribute implements Serializable, JsonSerializable
{

    public readonly ReflectionClass $class;
    public readonly object $object;

    public function getClassObject(): ?object
    {
        try {
            $class = $this->class->name;
            $this->object ??= new $class();
        } catch (\Throwable $th) {
            // throw new Exception(vsprintf('Error %s line %d: object of class %s is not defined and can not be instancied.', [__METHOD__, __LINE__, $this->class->name]));
        }
        return $this->object ?? null;
    }

    public function setObject(object $object): static
    {
        $this->object = $object;
        return $this;
    }

    public function setClass(object $class): static
    {
        if(!($class instanceof ReflectionClass)) {
            $this->object = $class;
            $class = new ReflectionClass($class);
        }
        $this->class = $class;
        return $this;
    }

    public function getClassname(): string
    {
        return $this->class->name;
    }

    public function jsonSerialize(): mixed
    {
        return $this->__serialize();
    }

    public function jsonUnserialize(string|array $data): void
    {
        if(is_string($data)) {
            $data = json_decode($data, true);
        }
        $this->__unserialize($data);
    }

    public function __serialize(): array
    {
        return [
            'attribute' => __CLASS__,
            'class' => $this->class->name,
        ];
    }

    public function __unserialize(array $data): void
    {
        if($data['attribute'] !== __CLASS__) {
            // Data does not match!
            throw new Exception(vsprintf('Error %s line %d: unserialize data (for %s) does not match for this Attribute %s!', [__METHOD__, __LINE__, $data['attribute'], __CLASS__]));
        }
        $this->setClass(new ReflectionClass($data['class']));
    }

    public function serialize()
    {
        return json_encode($this->__serialize());
    }

    public function unserialize(string $data)
    {
        $data = json_decode($data, true);
        $this->__unserialize($data);
    }

}