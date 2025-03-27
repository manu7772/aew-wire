<?php
namespace Aequation\WireBundle\Tools;

use Aequation\WireBundle\Tools\interface\ToolInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributeClassInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributeMethodInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributeConstantInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributePropertyInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
// PHP
use Attribute;
use Exception;
use ReflectionClass;
use ReflectionAttribute;
use ReflectionClassConstant;
use Stringable;
use Twig\Markup;

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
     * DEBUG PRINT
     *************************************************************************************/

    public static function toDebugString(
        mixed $something,
        bool $expanded = false
    ): Markup
    {
        switch (true) {
            case is_null($something):
                $string = '[null]';
                break;
            case is_bool($something):
                $string = $something ? '[bool]true' : '[bool]false';
                break;
            case is_object($something) && $something instanceof WireEntityInterface:
                $string = vsprintf('%s "%s" (#%d)', [$something->getClassname(), $something, $something->getId() ?? 'null']);
                break;
            case is_object($something) && $something instanceof Stringable:
                $string = vsprintf('%s "%s"', [$something::class, $something]);
                break;
            case is_object($something):
                $string = $something::class;
                break;
            case is_iterable($something):
                $string = vsprintf('[%s] %s', [gettype($something), $expanded ? json_encode($something, JSON_PRETTY_PRINT) : count($something).' items']);
                break;
            case is_scalar($something):
                $string = vsprintf('[%s] %s', [gettype($something), $something]);
                break;
            default:
                $string = gettype($something);
                break;
        }
        return Strings::markup($string);
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

    public static function isAlmostOneOfIntefaces(
        object|string $class,
        string|array $interfaces
    ): bool
    {
        foreach ((array)$interfaces as $interface) {
            if(is_a($class, $interface, true)) return true;
        }
        return false;
    }

    /*************************************************************************************
     * ATTRIBUTES
     *************************************************************************************/

    public static function getClassAttributes(
        object|string $objectOrClass,
        ?string $attributeClass = null
    ): array
    {
        if($objectOrClass instanceof ReflectionClass) {
            $reflClass = $objectOrClass;
            $objectOrClass = $reflClass->name;
        } else {
            $reflClass = new ReflectionClass($objectOrClass);
        }
        $attributes = [];
        /** @var ReflectionClass $reflClass */
        foreach ($reflClass->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute = $attribute->newInstance();
            if($attribute instanceof AppAttributeClassInterface) {
                $attribute->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
            }
            $attributes[] = $attribute;
        }
        if(empty($attributes) && $parent = $reflClass->getParentClass()) {
            // Try find in parent classes (recursively)
            $attributes = static::getClassAttributes($parent, $attributeClass);
        }
        return $attributes;
    }

    public static function getPropertyAttributes(
        object|string $objectOrClass,
        ?string $attributeClass = null,
        bool $searchInParents = true,
    ): array
    {
        throw new Exception('Method not implemented yet!');
        // if($objectOrClass instanceof ReflectionClass) {
        //     $reflClass = $objectOrClass;
        //     $objectOrClass = $reflClass->name;
        // } else {
        //     $reflClass = new ReflectionClass($objectOrClass);
        // }
        // $propertys = $reflClass->getProperties();
        // $attributes = [];
        // foreach ($propertys as $property) {
        //     foreach ($property->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
        //         if($attr->getTarget() & Attribute::TARGET_PROPERTY === Attribute::TARGET_PROPERTY) {
        //             $attr = $attr->newInstance();
        //             if($attr instanceof AppAttributePropertyInterface) {
        //                 $attr->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
        //                 $attr->setProperty($property);
        //             }
        //             $attributes[$property->name] ??= [];
        //             $attributes[$property->name][] = $attr;
        //         }
        //     }
        // }
        // if($searchInParents) {
        //     // Try find in parent class (recursively)
        //     if($parent = $reflClass->getParentClass()) {
        //         foreach (static::getPropertyAttributes($parent, $attributeClass, true) as $attrname => $attr) {
        //             $attributes[$attrname] ??= $attr;
        //         }
        //     }
        // }
        // return $attributes;
    }

    public static function getMethodAttributes(
        object|string $objectOrClass,
        ?string $attributeClass = null,
        ?int $filter = null
    ): array
    {
        if($objectOrClass instanceof ReflectionClass) {
            $reflClass = $objectOrClass;
            $objectOrClass = $reflClass->name;
        } else {
            $reflClass = new ReflectionClass($objectOrClass);
        }
        $methods = $reflClass->getMethods($filter);
        $attributes = [];
        foreach ($methods as $method) {
            foreach ($method->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $attribute = $attribute->newInstance();
                if($attribute instanceof AppAttributeMethodInterface) {
                    $attribute->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
                    $attribute->setMethod($method);
                }
                $attributes[$method->name] ??= [];
                $attributes[$method->name][] = $attribute;
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
        throw new Exception('Method not implemented yet!');
        // if($objectOrClass instanceof ReflectionClass) {
        //     $reflClass = $objectOrClass;
        //     $objectOrClass = $reflClass->name;
        // } else {
        //     $reflClass = new ReflectionClass($objectOrClass);
        // }
        // $constants = $reflClass->getConstants();
        // $attributes = [];
        // foreach ($constants as $name => $value) {
        //     $reflClassConstant = new ReflectionClassConstant($objectOrClass, $name);
        //     foreach ($reflClassConstant->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
        //         if($attr->getTarget() & Attribute::TARGET_CLASS_CONSTANT === Attribute::TARGET_CLASS_CONSTANT) {
        //             $attr = $attr->newInstance();
        //             if($attr instanceof AppAttributeConstantInterface) {
        //                 // $attr->setConstant($constant);
        //                 $attr->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
        //                 $attr->setConstant($reflClassConstant);
        //                 // $attr->setValue($value);
        //             }
        //             $attributes[$reflClassConstant->name] ??= [];
        //             $attributes[$reflClassConstant->name][] = $attr;
        //         }
        //     }
        // }
        // if($searchInParents) {
        //     // Try find in parent class (recursively)
        //     if($parent = $reflClass->getParentClass()) {
        //         foreach (static::getConstantAttributes($parent, $attributeClass, true) as $attrname => $attr) {
        //             $attributes[$attrname] ??= $attr;
        //         }
        //     }
        // }
        // return $attributes;
    }

}