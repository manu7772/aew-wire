<?php
namespace Aequation\WireBundle\DependencyInjection;

use Aequation\WireBundle\AequationWireBundle;
use Aequation\WireBundle\AequationWireCompilerPass;
// Symfony
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\Definition\ConfigurationInterface;
// PHP
use Exception;

class AequationWireExtension extends Extension implements PrependExtensionInterface
{

    public const CONFIGURES = [
        'Parameters' => true,
        // 'Siteparams' => false,
        "Framework" => true,
        'Twig' => true,
        'TwigComponent' => true,
        'Tailwind' => false, // --> disabled for now
        // 'VichUploader' => false,
        'LiipImagine' => true,
        'AssetMapper' => false, // --> disabled for now
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(AequationWireBundle::getPackagePath('config'))
        );
        $loader->load('services.yaml');
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
    }

    public function prepend(ContainerBuilder $container): void
    {
        foreach (static::CONFIGURES as $name => $enabled) {
            // control if configured in 
            if($enabled && (AequationWireCompilerPass::CONFIGURES[$name] ?? false)) {
                throw new Exception(vsprintf('Error %s line %d: "%s" parameters are already configured in %s!', [__METHOD__, __LINE__, $name, AequationWireCompilerPass::class]));
                $enabled = false;
            }
            if($enabled) {
                WireConfigurators::configure($name, $container, true);
            }
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new Configuration($config, $container);
    }

}
