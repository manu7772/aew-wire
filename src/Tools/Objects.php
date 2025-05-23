<?php
namespace Aequation\WireBundle\Tools;

use Aequation\WireBundle\Tools\interface\ToolInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributeClassInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributeMethodInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributeConstantInterface;
use Aequation\WireBundle\Attribute\interface\AppAttributePropertyInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
// Symfony
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
// PHP
use Attribute;
use Exception;
use ReflectionClass;
use ReflectionAttribute;
use ReflectionClassConstant;
use Stringable;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;
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
    ): ?string
    {
        if(is_string($objectOrClass) && !class_exists($objectOrClass)) {
            return null;
        }
        $RC = new ReflectionClass($objectOrClass);
        return $lowercase
            ? strtolower($RC->getShortName())
            : $RC->getShortName();
    }

    public static function getClassname(
        object $objectOrClass
    ): ?string
    {
        if($objectOrClass instanceof BaseEntityInterface) {
            return $objectOrClass->getClassname();
        }
        $RC = new ReflectionClass($objectOrClass);
        return $RC->getName();
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
     * ACCESSORS
     *************************************************************************************/

    public static function newAccessor(): PropertyAccessorInterface
    {
        $accessor = PropertyAccess::createPropertyAccessorBuilder();
        $accessor->enableExceptionOnInvalidIndex();
        $accessor->enableMagicCall();
        return $accessor->getPropertyAccessor();
    }

    public static function getPropertyValue(
        object $object,
        string $property
    ): mixed
    {
        $accessor = static::newAccessor();
        return $accessor->getValue($object, $property);
    }

    public static function setPropertyValue(
        object $object,
        string $property,
        mixed $value
    ): bool
    {
        $accessor = static::newAccessor();
        try {
            $accessor->setValue($object, $property, $value);
        } catch (Throwable $th) {
            return false;
        }
        return true;
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
            case is_object($something) && $something instanceof BaseEntityInterface:
                $string = vsprintf('%s "%s" (#%d) U:%s', [$something->getClassname(), $something, $something->getId() ?? 'null', $something->getUnameThenEuid()]);
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

    public static function toDump(
        mixed $something,
        bool $asHtml = false,
        int $depth = 3,
        ?NormalizerInterface $normalizer = null,
        array $groups = ['debug'],
        int $start_depth = 0,
    ): ?Markup
    {
        if($start_depth >= $depth) {
            return null;
        }
        $prefix = empty($start_depth) ? '' : ' : ';
        if($asHtml) {
            $start_section = empty($start_depth)
                ? '<div style="margin-left: 0px; padding: 4px; background-color: black; border-radius: 6px; font-size: 14px; line-height: 16px; width: 100%; color: white; border: 1px solid blue;">'
                : '<span style="margin-left: 0px; padding: 4px; font-size: 14px; line-height: 16px;">'
                ;
            $end_section = empty($start_depth) ? '</div>' : '</span>';
            $start_line = $prefix;
            $end_line = null;
            $start_type = '<span style="color: blue; font-weight: thin; font-style: italic;">';
            $end_type = '</span>&nbsp;';
            $start_value = '<span style="color: lime; font-weight: bold;">';
            $end_value = '</span>';
            $start_ul = '<ul style="margin-left: 16px; width: 100%; list-style-type: none;">';
            $end_ul = '</ul>';
            $start_li = '<li style="width: 100%;">';
            $end_li = '</li>';
        } else {
            $start_section = null;
            $end_section = null;
            $start_line = str_pad('', $start_depth * 4, ' ', STR_PAD_LEFT).$prefix;
            $end_line = PHP_EOL;
            $start_type = null;
            $end_type = ' ';
            $start_value = null;
            $end_value = null;
            $start_ul = null;
            $end_ul = null;
            $start_li = null;
            $end_li = null;
        }
        $string = $start_section;
        switch (true) {
            case is_null($something) || is_bool($something):
                $string .= vsprintf('%s%s[%s]%s %s%s%s%s', [$start_line, $start_type, gettype($something), $end_type, $start_value, json_encode($something), $end_value, $end_line]);
                break;
            case is_object($something) && $something instanceof BaseEntityInterface:
                $string = vsprintf('%s%s[%s] %s (#%d) U:%s%s', [$start_line, $start_type, $something->getClassname(), $something->__toString(), $something->getId() ?? 'null', $something->getUnameThenEuid(), $end_value]);
                if($normalizer) {
                    $data = $normalizer->normalize($something, null, ['groups' => $groups]);
                    if(is_iterable($data)) {
                        $string .= $start_ul;
                        foreach ($data as $key => $value) {
                            $string .= vsprintf('%s%s %s %s', [$start_li, $key, static::toDump($value, $asHtml, $depth, $normalizer, $groups, $start_depth + 1), $end_li]);
                        }
                        $string .= $end_ul;
                    } else {
                        $string .= vsprintf('%s%s[%s]%s %s%s%s%s', [$start_line, $start_type, gettype($data), $end_type, $start_value, $data, $end_value, $end_line]);
                    }
                }
                $string .= vsprintf('%s', [$end_line]);
                break;
            case is_object($something) && $something instanceof Stringable:
                $string .= vsprintf('%s%s[%s]%s %s%s%s%s', [$start_line, $start_type, get_class($something), $end_type, $start_value, $something->__toString(), $end_value, $end_line]);
                break;
            case is_object($something) && !is_iterable($something):
                $string .= vsprintf('%s%s[%s]%s %s%s%s%s', [$start_line, $start_type, get_class($something), $end_type, $start_value, null, $end_value, $end_line]);
                break;
            case is_iterable($something):
                $string .= vsprintf('%s%s[%s]%s %s%s%s', [$start_line, $start_type, gettype($something), $end_type, $start_value, count($something).' elements', $end_value]);
                if(count($something) > 0) {
                    $string .= $start_ul;
                    foreach ($something as $key => $value) {
                        $string .= vsprintf('%s%s %s %s', [$start_li, $key, static::toDump($value, $asHtml, $depth, $normalizer, $groups, $start_depth + 1), $end_li]);
                    }
                    $string .= $end_ul;
                }
                $string .= vsprintf('%s', [$end_line]);
                break;
            case is_scalar($something):
                $string .= vsprintf('%s%s[%s]%s %s%s%s%s', [$start_line, $start_type, gettype($something).(is_string($something) ? ' - length: '.strlen($something).' char.' : ''), $end_type, $start_value, $something, $end_value, $end_line]);
                break;
            default:
                // $string = gettype($something);
                break;
        }
        $string .= $end_section;
        return Strings::markup($string);
    }

    public static function printUname(
        null|string|UnameInterface $uname
    ): string|array
    {
        return $uname instanceof UnameInterface ? $uname->getSelfState()->getReport() : json_encode($uname);
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
            if(false === $parent = $reflClass->getParentClass()) {
                return false;
            }
            $reflClass = $parent;
        }
        return $reflClass;
    }

    /**
     * Get filtered list of classes
     * Param $listOfClasses can be:
     * - string => classname, object or regex
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
        $classes = get_declared_classes();
        if(empty($listOfClasses)) {
            $listOfClasses = $classes;
            if($sort) sort($listOfClasses);
            return;
        }
        if(is_string($listOfClasses)) {
            if(!class_exists($listOfClasses)) {
                // filter with REGEX
                $regex = $listOfClasses;
                $listOfClasses = [];
                foreach ($classes as $class) {
                    if(preg_match($regex, $class)) $listOfClasses[] = $class;
                }
                if($sort) sort($listOfClasses);
                return;
            }
            $listOfClasses = [$listOfClasses];
        }
        if(is_object($listOfClasses)) {
            $listOfClasses = [$listOfClasses];
        }
        $listOfClasses = array_filter($listOfClasses, function ($class) use ($classes) {
            if(is_object($class)) {
                $class = $class::class;
            }
            return is_string($class) && in_array($class, $classes);
        });
        if($sort) sort($listOfClasses);
    }

    /**
     * Get filtered list of classes
     * Param $interfaces can be:
     * - string => interface/classname or regex
     * - array of string interfaces/classnames
     * - empty (null or []) => uses all declared interfaces (get_declared_interfaces() + get_declared_classes() if $assumeClasses = true)
     * 
     * @param array|string|null &$interfaces
     * @return void
     */
    public static function filterDeclaredInterfaces(
        null|array|string &$interfaces = null,
        bool $sort = false,
        bool $assumeClasses = false
    ): void
    {
        $classes = $assumeClasses ? array_merge(get_declared_classes(), get_declared_interfaces()) : get_declared_interfaces();
        if(empty($interfaces)) {
            $interfaces = $classes;
            if($sort) sort($interfaces);
            return;
        }
        if(is_string($interfaces)) {
            if(!interface_exists($interfaces) && !class_exists($interfaces)) {
                // filter with REGEX
                $regex = $interfaces;
                $interfaces = [];
                foreach ($classes as $class) {
                    if(preg_match($regex, $class)) $interfaces[] = $class;
                }
                if($sort) sort($interfaces);
                return;
            }
            $interfaces = [$interfaces];
        }
        $interfaces = array_filter($interfaces, fn ($interface) => is_string($interface) && in_array($interface, $classes));
        if($sort) sort($interfaces);
    }

    /**
     * Filter classes that must be almost one of interfaces/classes
     * - If $interfaces is empty, uses all declared interfaces (or interfaces + classes if $assumeClasses = true)
     * - If $listOfClasses is empty, uses all declared classes
     * 
     * @param string|array $interfaces
     * @param null|array|object|string $listOfClasses
     * @return array
     */
    public static function filterByInterface(
        string|array $interfaces,
        null|array|object|string $listOfClasses = null,
        bool $assumeClasses = true
    ): array
    {
        static::filterDeclaredInterfaces($interfaces, true, $assumeClasses);
        static::filterDeclaredClasses($listOfClasses);
        return array_filter($listOfClasses, function ($classname) use ($interfaces) {
            foreach ($interfaces as $interface) {
                if(is_a($classname, $interface, true)) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Check if class is almost one of interfaces/classes
     * 
     * @param object|string $class
     * @param string|array $interfaces
     * @return bool
     */
    public static function isAlmostOneOfIntefaces(
        object|string $class,
        string|array $interfaces // --> empty or '*' = all interfaces
    ): bool
    {
        if(is_object($class)) {
            $class = $class::class;
        }
        if(!class_exists($class)) {
            return false;
        }
        if(empty($interfaces) || $interfaces === '*') return true;
        foreach ((array)$interfaces as $interface) {
            if(
                interface_exists($interface)
                && class_exists($interface)
                && is_a($class, $interface, true)
            ) {
                return true;
            }
        }
        return false;
    }

    public static function getFinalInterfaces(string $interface): array
    {
        $RC = new ReflectionClass($interface);
        $interfaces = [];
        foreach ($RC->getInterfaces() as $interface) {
            if($interface->isFinal()) {
                $interfaces[] = $interface;
            }
        }
        return $interfaces;
    }

    /**
     * Change class/interface names to class names
     * - Find all classes are subclasses of classes or that implements the interfaces)
     * - If $classChoices is empty, uses all declared classes
     * - If $classChoices is not empty, uses only classes that are declared in $classChoices
     * 
     * @param array $classesAndInterfaces
     * @param null|array $classChoices
     * @return array
     */
    public static function changeToClassnames(
        array $classesAndInterfaces,
        ?array $classChoices = null
    ): array
    {
        static::filterDeclaredClasses($classChoices);
        $classes = [];
        foreach ($classesAndInterfaces as $classOrInterface) {
            foreach ($classChoices as $classname) {
                if(is_subclass_of($classname, $classOrInterface, true) || is_a($classname, $classOrInterface, true)) {
                    $classes[$classname] = $classname;
                }
            }
        }
        // dd($classChoices, $classesAndInterfaces, array_values($classes));
        return array_values($classes);
    }

    /*************************************************************************************
     * ATTRIBUTES
     *************************************************************************************/

    public static function getClassAttributes(
        object|string $objectOrClass,
        ?string $attributeClass = null
    ): array
    {
        if(!($objectOrClass instanceof ReflectionClass)) {
            $classname = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;
            if(!class_exists($classname)) return [];
        }
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