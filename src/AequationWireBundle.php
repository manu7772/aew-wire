<?php
namespace Aequation\WireBundle;

use Aequation\WireBundle\Tools\Files;
// Symfony
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AequationWireBundle extends Bundle
{

    public static function getPackagePath(
        ?string $path = null,
        int $levels = 1,
        bool $directory_separator = false
    ): string
    {
        $base = \dirname(__DIR__, $levels);
        if(preg_match('/^(\/?bundles\/)/', $base)) {
            // To vendor path (instead of symlink local path)
            // dump($base, preg_match('/^(\/?bundles\/aequation\/aew-wire)/', $base, $matches));
            // dump($matches);
            $base = preg_replace('/^(\/?bundles\/aequation\/aew-wire)/', '/workspace/vendor/aequation/wire', $base);
            // dump(pathinfo(__DIR__), __DIR__, pathinfo($base), $base, $path, '-------------------------------------------------');
        }
        $new_path = Files::addPath($base, $path).($directory_separator ? DIRECTORY_SEPARATOR : '');
        if(!file_exists($new_path)) {
            $message = vsprintf('Error %s line %d: path "%s" not found! (searched with base "%s" and added path "%s").', [__METHOD__, __LINE__, $new_path, $base, $path]);
            trigger_error($message, E_USER_ERROR);
        }
        // dd($new_path);
        return $new_path;
    }

    public function getPath(): string
    {
        return static::getPackagePath(null, 1);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AequationWireCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
        parent::build($container);
    }

}