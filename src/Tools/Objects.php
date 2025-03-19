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
     * FIND CLASSES
     *************************************************************************************/

     /**
     * Retrieve class or parent class that contains the DECLARED property
     * @param object|string $class
     * @param string $propertyName
     * @return ReflectionClass|false
     */
    public static function getClassDeclaratorOfProperty(
        object|string $class,
        string $propertyName
    ): ReflectionClass|false
    {
        $reflClass = new ReflectionClass($class);
        while (!$reflClass->hasProperty($propertyName)) {
            $parent = $reflClass->getParentClass();
            if(!$parent) return false;
            $reflClass = $parent;
        }
        return $reflClass;
    }

    /**
     * Get filtered list of classes
     * param $listOfClasses can be:
     * - single => classname, object or regex
     * - array of classnames or objects
     * - empty (null or []) => uses all declared classes (get_declared_classes())
     * @param array|object|string|null &$listOfClasses
     * @return void
     */
    public static function filterDeclaredClasses(
        null|array|object|string &$listOfClasses = null,
        bool $sort = false
    ): void
    {
        if(empty($listOfClasses)) $listOfClasses = get_declared_classes();
        if(is_string($listOfClasses) && !class_exists($listOfClasses)) {
            // filter with REGEX
            $regex = $listOfClasses;
            $listOfClasses = [];
            foreach (get_declared_classes() as $class) {
                if(preg_match($regex, $class)) $listOfClasses[] = $class;
            }
        }
        if(!is_array($listOfClasses)) $listOfClasses = [$listOfClasses];
        if($sort) sort($listOfClasses);
    }

    /**
     * Get filtered list of classes
     * param $interfaces can be:
     * - single => classname or regex
     * - array of interfaces classnames
     * - empty (null or []) => uses all declared interfaces (get_declared_interfaces())
     * @param array|object|string|null &$interfaces
     * @return void
     */
    public static function filterDeclaredInterfaces(
        null|array|object|string &$interfaces = null,
        bool $sort = false
    ): void
    {
        if(empty($interfaces)) $interfaces = get_declared_interfaces();
        if(is_string($interfaces) && !interface_exists($interfaces)) {
            // filter with REGEX
            $regex = $interfaces;
            $interfaces = [];
            foreach (get_declared_interfaces() as $class) {
                if(preg_match($regex, $class)) $interfaces[] = $class;
            }
        }
        if(!is_array($interfaces)) $interfaces = [$interfaces];
        if($sort) sort($interfaces);
    }

    /**
     * Get classes of interface
     * @param string|array $interfaces
     * @param array|null $listOfClasses
     * @return array
     */
    public static function filterByInterface(
        string|array $interfaces,
        null|array|object|string $listOfClasses = null
    ): array
    {
        static::filterDeclaredInterfaces($interfaces);
        static::filterDeclaredClasses($listOfClasses);
        return array_filter($listOfClasses, function ($classname) use ($interfaces) {
            foreach ($interfaces as $interface) {
                if(is_a($classname, $interface, true)) return true;
            }
            return false;
        });
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