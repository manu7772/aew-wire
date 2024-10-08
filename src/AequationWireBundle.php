<?php
namespace Aequation\WireBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AequationWireBundle extends Bundle
{

    public static function getProjectPath(
        int $level = 1,
        bool $directory_separator = false
    ): string
    {
        return \dirname(__DIR__, $level).($directory_separator ? DIRECTORY_SEPARATOR : '');
    }

    public function getPath(): string
    {
        return static::getProjectPath(1, false);
    }

}