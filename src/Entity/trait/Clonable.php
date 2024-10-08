<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitClonableInterface;
use Exception;
use ReflectionClass;

/**
 * A *trait* that allows you to clone readonly properties in PHP 8.1
 * @see https://github.com/spatie/php-cloneable
 * @see https://github.dev/spatie/php-cloneable [vscode vue]
 * From
 * @see https://github.com/spatie
 * @see https://github.com/spatie/package-skeleton-php
 * @see https://stitcher.io/blog/cloning-readonly-properties-in-php-81
 */
trait Clonable
{

    public const IS_CLONABLE = true;

    public function __construct_clonable(): void
    {
        if(!($this instanceof TraitClonableInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitClonableInterface::class]));
    }

    public function with(...$values): static
    {
        $refClass = new ReflectionClass(static::class);
        $clone = $refClass->newInstanceWithoutConstructor();
        foreach ($refClass->getProperties() as $property) {
            if (!$property->isStatic()) {
                $objectField = $property->getName();
                if (array_key_exists($objectField, $values)) {
                    $objectValue = $values[$objectField];
                } else if($property->isInitialized($this)) {
                    $objectValue = $property->getValue($this);
                } else {
                    continue;
                }
                $declarationScope = $property->getDeclaringClass()->getName();
                if ($declarationScope === self::class) {
                    $clone->$objectField = $objectValue;
                } else {
                    (fn () => $this->$objectField = $objectValue)
                        ->bindTo($clone, $declarationScope)();
                }
            }
        }
        return $clone;
    }
}