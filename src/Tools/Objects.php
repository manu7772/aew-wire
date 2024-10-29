<?php
namespace Aequation\WireBundle\Tools;

use Aequation\WireBundle\Tools\interface\ToolInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributeClassInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributeMethodInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributeConstantInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributePropertyInterface;
// PHP
use Attribute;
use ReflectionClass;
use ReflectionAttribute;
use ReflectionClassConstant;

class Objects implements ToolInterface
{

    public function __toString(): string
    {
        return static::getShortname(static::class, false);
    }


    /*************************************************************************************
     * NAMES
     *************************************************************************************/

    public static function getShortname(
        object|string $objectOrClass,
        bool $lowercase = false
    ): string
    {
        $RC = new ReflectionClass($objectOrClass);
        return $lowercase
            ? strtolower($RC->getShortName())
            : $RC->getShortName();
    }


    /*************************************************************************************
     * TESTS
     *************************************************************************************/

    public static function isInstantiable(string $classname): bool
    {
        $RC = new ReflectionClass($classname);
        return $RC->isInstantiable();
    }


    /*************************************************************************************
     * ATTRIBUTES
     *************************************************************************************/

    public static function getClassAttributes(
        object|string $objectOrClass,
        ?string $attributeClass = null,
        bool $searchInParents = true,
    ): array
    {
        if($objectOrClass instanceof ReflectionClass) {
            $reflClass = $objectOrClass;
            $objectOrClass = $reflClass->name;
        } else {
            $reflClass = new ReflectionClass($objectOrClass);
        }
        $attributes = $reflClass->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);
        foreach ($attributes as $key => $attr) {
            if($attr->getTarget() & Attribute::TARGET_CLASS === Attribute::TARGET_CLASS) {
                $attributes[$key] = $attr = $attr->newInstance();
                if($attr instanceof AppAttributeClassInterface) $attr->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
            }
        }
        if(empty($attributes) && $searchInParents) {
            // Try find in parent class (recursively)
            $parent = $reflClass->getParentClass();
            if($parent) return static::getClassAttributes($parent, $attributeClass, true);
        }
        return $attributes;
    }

    public static function getPropertyAttributes(
        object|string $objectOrClass,
        ?string $attributeClass = null,
        bool $searchInParents = true,
    ): array
    {
        if($objectOrClass instanceof ReflectionClass) {
            $reflClass = $objectOrClass;
            $objectOrClass = $reflClass->name;
        } else {
            $reflClass = new ReflectionClass($objectOrClass);
        }
        $propertys = $reflClass->getProperties();
        $attributes = [];
        foreach ($propertys as $property) {
            foreach ($property->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                if($attr->getTarget() & Attribute::TARGET_PROPERTY === Attribute::TARGET_PROPERTY) {
                    $attr = $attr->newInstance();
                    if($attr instanceof AppAttributePropertyInterface) {
                        $attr->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
                        $attr->setProperty($property);
                    }
                    $attributes[$property->name] ??= [];
                    $attributes[$property->name][] = $attr;
                }
            }
        }
        if($searchInParents) {
            // Try find in parent class (recursively)
            if($parent = $reflClass->getParentClass()) {
                foreach (static::getPropertyAttributes($parent, $attributeClass, true) as $attrname => $attr) {
                    $attributes[$attrname] ??= $attr;
                }
            }
        }
        return $attributes;
    }

    public static function getMethodAttributes(
        object|string $objectOrClass,
        ?string $attributeClass = null,
        bool $searchInParents = true,
    ): array
    {
        if($objectOrClass instanceof ReflectionClass) {
            $reflClass = $objectOrClass;
            $objectOrClass = $reflClass->name;
        } else {
            $reflClass = new ReflectionClass($objectOrClass);
        }
        $methods = $reflClass->getMethods();
        $attributes = [];
        foreach ($methods as $method) {
            foreach ($method->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                if($attr->getTarget() & Attribute::TARGET_METHOD === Attribute::TARGET_METHOD) {
                    $attr = $attr->newInstance();
                    if($attr instanceof AppAttributeMethodInterface) {
                        $attr->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
                        $attr->setMethod($method);
                    }
                    $attributes[$method->name] ??= [];
                    $attributes[$method->name][] = $attr;
                }
            }
        }
        if($searchInParents) {
            // Try find in parent class (recursively)
            if($parent = $reflClass->getParentClass()) {
                foreach (static::getMethodAttributes($parent, $attributeClass, true) as $attrname => $attr) {
                    $attributes[$attrname] ??= $attr;
                }
            }
        }
        return $attributes;
    }

    public static function getConstantAttributes(
        object|string $objectOrClass,
        ?string $attributeClass = null,
        bool $searchInParents = true,
    ): array
    {
        if($objectOrClass instanceof ReflectionClass) {
            $reflClass = $objectOrClass;
            $objectOrClass = $reflClass->name;
        } else {
            $reflClass = new ReflectionClass($objectOrClass);
        }
        $constants = $reflClass->getConstants();
        $attributes = [];
        foreach ($constants as $name => $value) {
            $reflClassConstant = new ReflectionClassConstant($objectOrClass, $name);
            foreach ($reflClassConstant->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                if($attr->getTarget() & Attribute::TARGET_CLASS_CONSTANT === Attribute::TARGET_CLASS_CONSTANT) {
                    $attr = $attr->newInstance();
                    if($attr instanceof AppAttributeConstantInterface) {
                        // $attr->setConstant($constant);
                        $attr->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
                        $attr->setConstant($reflClassConstant);
                        // $attr->setValue($value);
                    }
                    $attributes[$reflClassConstant->name] ??= [];
                    $attributes[$reflClassConstant->name][] = $attr;
                }
            }
        }
        if($searchInParents) {
            // Try find in parent class (recursively)
            if($parent = $reflClass->getParentClass()) {
                foreach (static::getConstantAttributes($parent, $attributeClass, true) as $attrname => $attr) {
                    $attributes[$attrname] ??= $attr;
                }
            }
        }
        return $attributes;
    }

}