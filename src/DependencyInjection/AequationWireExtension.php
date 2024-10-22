<?php
namespace Aequation\WireBundle\DependencyInjection;

use Aequation\WireBundle\AequationWireBundle;
use Aequation\WireBundle\AequationWireCompilerPass;
// Symfony
use Symfony\Component\Config\FileLocator;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
// PHP
use Exception;

class AequationWireExtension extends Extension implements PrependExtensionInterface
{

    public const CONFIGURES = [
        'Twig' => true,
        'VichUploader' => false,
        'AssetMapper' => true,
    ];

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(AequationWireBundle::getPackagePath('config'))
        );
        $loader->load('services.yaml');
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
    }

    public function prepend(ContainerBuilder $container)
    {
        foreach (static::CONFIGURES as $name => $enabled) {
            // control if configured in 
            if($enabled && (AequationWireCompilerPass::CONFIGURES[$name] ?? false)) {
                throw new Exception(vsprintf('Error %s line %d: "%s" parameters are already configured in %s!', [__METHOD__, __LINE__, $name, AequationWireCompilerPass::class]));
                $enabled = false;
            }
            if($enabled) {
                $method = "configure".$name;
                $this->$method($container);
            }
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($config, $container);
    }


    /**
     * ALL CONFIGURATIONS
     */

    // TWIG

    protected function configureTwig(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('twig', [
            'globals' => ['app' => '@Aequation\WireBundle\Service\interface\AppWireServiceInterface']
        ]);
    }

    // VICH UPLOADER

    protected function configureVichUploader(ContainerBuilder $container): void
    {
        $added_mappings = $this->getVichUploaderMappings();
        $origin_mappings = $container->hasParameter('vich_uploader.mappings') ? $container->getParameter('vich_uploader.mappings') : [];
        foreach ($added_mappings as $name => $values) {
            $origin_mappings[$name] ??= $values;
        }
        $container->prependExtensionConfig('vich_uploader', [
            'metadata' => ['type' => 'attribute'],
            'mappings' => $origin_mappings,
        ]);
    }

    // ASSET MAPPER

    protected function configureAssetMapper(ContainerBuilder $container): void
    {
        if($this->isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        AequationWireBundle::getPackagePath('/assets/dist') => '@aequation/ux-wire-utilities',
                    ],
                ],
            ]);
        }
    }



    /**
     * Utilities
     */

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if(!interface_exists(AssetMapperInterface::class)) {
            return false;
        }
        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if(!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }
        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }

    private function getVichUploaderMappings(): array
    {
        return [
            'photo' => [
                'uri_prefix' => '%vich_dirs.item_photo%',
                'upload_destination' => '%kernel.project_dir%/public%vich_dirs.item_photo%',
                'namer' => [
                    'service' => 'Vich\UploaderBundle\Naming\SmartUniqueNamer',
                    'options' => [],
                ],
                'delete_on_update' => true,
                'delete_on_remove' => true,
            ],
            'portrait' => [
                'uri_prefix' => '%vich_dirs.user_portrait%',
                'upload_destination' => '%kernel.project_dir%/public%vich_dirs.user_portrait%',
                'namer' => [
                    'service' => 'Vich\UploaderBundle\Naming\SmartUniqueNamer',
                    'options' => [],
                ],
                'delete_on_update' => true,
                'delete_on_remove' => true,
            ],
            'slide' => [
                'uri_prefix' => '%vich_dirs.slider_slides%',
                'upload_destination' => '%kernel.project_dir%/public%vich_dirs.slider_slides%',
                'namer' => [
                    'service' => 'Vich\UploaderBundle\Naming\SmartUniqueNamer',
                    'options' => [],
                ],
                'delete_on_update' => true,
                'delete_on_remove' => true,
            ],
            'pdf' => [
                'uri_prefix' => '%vich_dirs.pdf%',
                'upload_destination' => '%kernel.project_dir%/public%vich_dirs.pdf%',
                'namer' => [
                    'service' => 'Vich\UploaderBundle\Naming\SmartUniqueNamer',
                    'options' => [],
                ],
                'delete_on_update' => true,
                'delete_on_remove' => true,
            ],
        ];
    }

}
