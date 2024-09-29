<?php
namespace Aequation\WireBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AequationWireBundle extends Bundle
{

    public static function getProjectPath(
        bool $directory_separator = true
    ): string
    {
        return \dirname(__DIR__, 1).($directory_separator ? DIRECTORY_SEPARATOR : '');
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

}