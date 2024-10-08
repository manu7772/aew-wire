<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitSerializableInterface;
// Symfony
use Symfony\Component\HttpFoundation\File\File;
// PHP
use Exception;
use ReflectionClass;

trait Serializable
{

    public const SERIALIZE_UNBIND = ['file','classname','shortname'];

    public function __construct_serializable(): void
    {
        if(!($this instanceof TraitSerializableInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitSerializableInterface::class]));
    }

    public function __serialize(): array
    {  
        $rc = new ReflectionClass($this);
        $serialized = [];
        foreach ($rc->getProperties() as $property) {
            if(!preg_match('/^_/', $property->name) && !in_array($property->name, static::SERIALIZE_UNBIND)) {
                $value = $this->{'get'.ucfirst($property->name)}();
                if(!($value instanceof File)) {
                    $serialized[$property->name] = $value;
                }
            }
        }
        return $serialized;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $name => $value) {
            $method = 'set'.ucfirst($name);
            if(method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

}