<?php
namespace Aequation\WireBundle\Tools;

use Aequation\WireBundle\Tools\interface\ToolInterface;

class Iterables implements ToolInterface
{

    public function __toString(): string
    {
        return Objects::getShortname(static::class, false);
    }

    public static function mergeArrays(...$arrays): array
    {
        $final = [];
        foreach ($arrays as $array) {
            foreach ($array as $name => $values) {
                if(is_array($values)) {
                    $to_spread = is_array($final[$name] ?? null) ? [$final[$name], $values] : [$values];
                    $final[$name] = static::mergeArrays(...$to_spread);
                } else {
                    $final[$name] = $values;
                }
            }
        }
        return $final;
    }

    public static function removeEmptyElements(
        array $array,
        ?callable $callback = null
    ): array
    {
        $array = array_map(fn($t) => is_string($t) ? Strings::markup(trim($t))->__toString() : $t, $array);
        return array_filter($array, $callback ?? fn ($item) => !empty($item));
    }

    public static function isArrayIndex(mixed $index): bool
    {
        return is_int($index) || (is_string($index) && preg_match_all('/^\w+$/i', $index));
    }


    /*************************************************************************************
     * HTML Attributes
     *************************************************************************************/

    public static function toClassList(
        array|string $classes,
        bool $asString = false,
        string $pattern = '/(\s*[\r\n\s,]+\s*)/',
    ): array|string
    {
        if(is_array($classes)) {
            $classes = array_filter($classes, fn($item) => is_string($item) && strlen(trim($item)) > 0);
            $classes = implode(' ', $classes);
        }
        $final_classes = [];
        foreach (preg_split($pattern, trim($classes), -1, PREG_SPLIT_NO_EMPTY) as $class) {
            $class = trim($class);
            if(preg_match('/^[a-zA-Z-_][\w-]*$/', $class)) $final_classes[$class] = $class;
        }
        return $asString
            ? implode(' ', $final_classes)
            : $final_classes;
    }

}