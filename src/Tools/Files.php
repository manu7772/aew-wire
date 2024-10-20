<?php
namespace Aequation\WireBundle\Tools;

use Aequation\WireBundle\Tools\interface\ToolInterface;

class Files implements ToolInterface 
{

    public function __toString(): string
    {
        return Objects::getShortname(static::class, false);
    }

    /**
     * Add $path to $base directory
     * Remove final /
     * Replace multiple consecutive / by only one
     * @param string $base
     * @param string|null $path
     * @return string
     */
    public static function addPath(
        string $base,
        ?string $path = null
    ): string
    {
        $path = empty($path)
            ? $base
            : preg_replace('#\\/*$#', '', $base).preg_replace(['#^\\/*#', '#\\/*$#'], ['/',''], $path);
        return preg_replace('#\\/{2,}#', '/', $path);
    }

}