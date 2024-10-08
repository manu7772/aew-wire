<?php
namespace Aequation\WireBundle\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Aequation\WireBundle\Builder\DataTableBuilder;
use Aequation\WireBundle\Builder\DataTableBuilderInterface;
use Aequation\WireBundle\Twig\DataTableExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class AequationWireExtension extends Extension implements PrependExtensionInterface
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        // $container
        //     ->setDefinition('datatable.builder', new Definition(DataTableBuilder::class))
        //     ->setPublic(false);
        // $container
        //     ->setAlias(DataTableBuilderInterface::class, 'datatable.builder')
        //     ->setPublic(false);
        // $container
        //     ->setDefinition('datatable.twig_extension', new Definition(DataTableExtension::class))
        //     ->addArgument(new Reference('stimulus.helper'))
        //     ->addTag('twig.extension')
        //     ->setPublic(false);
    }

    public function prepend(ContainerBuilder $container)
    {
        if ($this->isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        __DIR__.'/../../assets/dist' => '@symfony/ux-wire-utilities',
                    ],
                ],
            ]);
        }
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($config, $container);
    }

}
