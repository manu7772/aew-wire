<?php
namespace Aequation\WireBundle\Tools;

use Aequation\WireBundle\Tools\interface\ToolInterface;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;

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


    /** YAML files */

    /**
     * Read a YAML file and return data
     * @param string|SplFileInfo $file
     * @return array|null
     */
    public function readYamlFile(
        string|SplFileInfo $file
    ): array|null
    {
        $filepath = $file instanceof SplFileInfo ? $file->getRealPath() : $file;
        return Yaml::parse(file_get_contents($filepath));
    }

}