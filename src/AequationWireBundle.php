<?php
namespace Aequation\WireBundle;

use Aequation\WireBundle\Tools\Files;
// Symfony
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
// PHP
use Exception;

class AequationWireBundle extends Bundle
{

    public static function getPackagePath(
        ?string $path = null,
        int $levels = 1,
        bool $directory_separator = false
    ): string
    {
        $base = \dirname(__DIR__, $levels);
        $new_path = Files::addPath($base, $path).($directory_separator ? DIRECTORY_SEPARATOR : '');
        if(!file_exists($new_path)) {
            $message = vsprintf('Error %s line %d: path "%s" not found! (searched with base "%s" and added path "%s").', [__METHOD__, __LINE__, $new_path, $base, $path]);
            throw new Exception($message);
        } else {
            $message = vsprintf('Info %s line %d: path "%s" found! (searched with base "%s" and added path "%s").', [__METHOD__, __LINE__, $new_path, $base, $path]);
            // dump($message);
        }
        // dd($path, __DIR__, $base, $new_path);
        return $new_path;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AequationWireCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
        parent::build($container);
    }

}