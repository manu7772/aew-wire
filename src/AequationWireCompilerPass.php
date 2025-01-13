<?php
namespace Aequation\WireBundle;

use Aequation\WireBundle\DependencyInjection\AequationWireExtension;
use Aequation\WireBundle\DependencyInjection\WireConfigurators;
// Symfony
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
// PHP
use Exception;

class AequationWireCompilerPass implements CompilerPassInterface
{
    public const CONFIGURES = [
        'Parameters' => false,
        'Twig' => false,
        'Tailwind' => false,
        'VichUploader' => false,
        'AssetMapper' => false,
    ];
    // public const EXECUTE_DISPATCH = true;
    // public const REMOVE_DISPATCHEDS = true;

    public function Process(
        ContainerBuilder $container
    ): void
    {
        foreach (static::CONFIGURES as $name => $enabled) {
            if($enabled && (AequationWireExtension::CONFIGURES[$name] ?? false)) {
                throw new Exception(vsprintf('Error %s line %d: "%s" parameters are already configured in %s!', [__METHOD__, __LINE__, $name, AequationWireExtension::class]));
                $enabled = false;
            }
            if($enabled) {
                WireConfigurators::configure($name, $container, false);
            }
        }
    }

}