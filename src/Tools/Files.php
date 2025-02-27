<?php
namespace Aequation\WireBundle\Tools;

use Aequation\WireBundle\Tools\interface\ToolInterface;
use Closure;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
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
    public static function readYamlFile(
        string|SplFileInfo $file
    ): array|null
    {
        $filepath = $file instanceof SplFileInfo ? $file->getRealPath() : $file;
        return Yaml::parse(file_get_contents($filepath));
    }


    /**
     * List files in Directory
     * Filter is array or Closure [ex. ->filter(static function (SplFileInfo $file) { ... })]
     * @param string $path
     * @param array|Closure|null $filter
     * @param integer $depth
     * @return array
     */
    public static function listFiles(
        string $path = null,
        array|Closure $filter = null,
        int $depth = 1,
    ): array
    {
        $finder = Finder::create()->ignoreUnreadableDirs()->files();
        if(!is_dir($path)) return [];
        $files = [];
        for ($i = 0; $i < $depth; $i++) {
            $finder->in($path)->depth($i);
            if($filter instanceof Closure) {
                $finder->filter($filter);
            } else if(is_array($filter) && count($filter) > 0) {
                $finder->name($filter);
            }
            $files = array_merge($files, iterator_to_array($finder, true));
        }
        return $files;
    }

}