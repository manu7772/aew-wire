<?php
namespace Aequation\WireBundle\DependencyInjection;

// Symfony
use Doctrine\DBAL\DriverManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\AssetMapper\AssetMapperInterface;
// PHP
use Exception;
use Throwable;

Class WireConfigurators
{

    public const EXECUTE_DISPATCH = true;
    public const REMOVE_DISPATCHEDS = true;

    public static function configure(
        string $name,
        ContainerBuilder $container, 
        bool $asPrepend
    ): void
    {
        switch ($name) {
            case 'Parameters':
                if($asPrepend) {
                    foreach (static::getParameters() as $name => $data) {
                        if(is_array($data) && !array_is_list($data)) {
                            foreach ($data as $key => $value) {
                                $sub_name = $name.'.'.$key;
                                if(is_array($value)) {
                                    $origin_named = $container->hasParameter($sub_name) ? $container->getParameter($sub_name) : [];
                                    if(is_array($origin_named)) {
                                        $value = array_merge($value, $origin_named);
                                        if(array_is_list($value)) $value = array_unique($value);
                                        // print('- '.$sub_name.' : '.json_encode($value).PHP_EOL);
                                        $container->setParameter($sub_name, $value);
                                    }
                                } else if (!$container->hasParameter($sub_name)) {
                                    // print('- '.$sub_name.' : '.json_encode($value).PHP_EOL);
                                    $container->setParameter($sub_name, $value);
                                }
                            }
                        } else if(!$container->hasParameter($name)) {
                            // print('- '.$name.' : '.json_encode($data).PHP_EOL);
                            $container->setParameter($name, $data);
                        }
                    }
                } else {
                    trigger_error(vsprintf('Error %s line %d: "%s" parameters are configured onlyu for prepend!', [__METHOD__, __LINE__, $name]), E_USER_WARNING);
                }
                break;
            // case 'Siteparams':
            //     if($asPrepend) {
            //         trigger_error(vsprintf('Error %s line %d: "%s" parameters are not configured for prepend!', [__METHOD__, __LINE__, $name]), E_USER_WARNING);
            //         return;
            //     }
            //     /** @var Connection $connexion */
            //     $connexion = DriverManager::getConnection(['url' => $_ENV['DATABASE_URL']]);
            //     try {
            //         $database_params = $connexion->executeQuery('SELECT P.name, P.paramvalue, P.dispatch FROM siteparams as P')->fetchAllAssociative();
            //     } catch (Throwable $th) {
            //         $database_params = false;
            //     }
            //     if(empty($database_params)) return;
            //     $params = array_map(
            //         function($param) {
            //             $param['paramvalue'] = json_decode($param['paramvalue'], true);
            //             return $param;
            //         },
            //         $database_params
            //     );
            //     if(static::EXECUTE_DISPATCH) {
            //         foreach ($params as $idx => $param) {
            //             $remove = false;
            //             if($param['dispatch'] && is_array($param['paramvalue']) && !array_is_list($param['paramvalue'])) {
            //                 foreach ($param['paramvalue'] as $key => $val) {
            //                     if(preg_match('/^[\w\d_-]+$/', $key)) {
            //                         $newid = $param['name'].'.'.$key;
            //                         $params[] = [
            //                             'name' => $newid,
            //                             'paramvalue' => $val,
            //                         ];
            //                         $remove = true;
            //                     }
            //                 }
            //             }
            //             if($remove && static::REMOVE_DISPATCHEDS) unset($params[$idx]);
            //         }
            //     }
            //     foreach ($params as $param) {
            //         $container->setParameter($param['name'], $param['paramvalue']);
            //     }
            //     break;
            case 'Framework':
                $origin_framework = $container->hasParameter('Framework') ? $container->getParameter('Framework') : [];
                $framework_config = array_merge(static::getFrameworkConfig(), $origin_framework);
                if($asPrepend) {
                    $container->prependExtensionConfig('Framework', $framework_config);
                } else {
                    trigger_error(vsprintf('Error %s line %d: "%s" parameters are not configured directly. Please use preprend mode!', [__METHOD__, __LINE__, $name]), E_USER_WARNING);
                }
                break;
            case 'Twig':
                $origin_twig = $container->hasParameter('twig') ? $container->getParameter('twig') : [];
                $twig_config = array_merge(static::getTwigConfig(), $origin_twig);
                if($asPrepend) {
                    $container->prependExtensionConfig('twig', $twig_config);
                } else {
                    trigger_error(vsprintf('Error %s line %d: "%s" parameters are not configured directly. Please use preprend mode!', [__METHOD__, __LINE__, $name]), E_USER_WARNING);
                }
                break;
            case 'TwigComponent':
                // Aequation\WireBundle\Twig\Components\: '@AequationWire/components/'
                if($asPrepend) {
                    $container->prependExtensionConfig('twig_component', [
                        'defaults' => [
                            'Aequation\\WireBundle\\Twig\\Components\\' => '@AequationWire/components/',
                        ],
                    ]);
                } else {
                    trigger_error(vsprintf('Error %s line %d: "%s" parameters are not configured directly. Please use preprend mode!', [__METHOD__, __LINE__, $name]), E_USER_WARNING);
                }
                break;
            case 'LiipImagine':
                $origin_twig = $container->hasParameter('liip_imagine') ? $container->getParameter('liip_imagine') : [];
                $liip_config = array_merge(static::getLiipImagineConfig(), $origin_twig);
                if($asPrepend) {
                    $container->prependExtensionConfig('liip_imagine', $liip_config);
                } else {
                    trigger_error(vsprintf('Error %s line %d: "%s" parameters are not configured directly. Please use preprend mode!', [__METHOD__, __LINE__, $name]), E_USER_WARNING);
                    $container->setParameter('liip_imagine', $liip_config);
                }
                break;
            case 'Tailwind':
                // trigger_error(vsprintf('OPTION "%s" (mode %s) NOT SUPPORTED YET %s line %d: this option is not available for now.', [$name, $asPrepend ? "PREPEND" : "NORMAL", __METHOD__, __LINE__]), E_USER_ERROR);
                if($asPrepend) {
                    $container->prependExtensionConfig('symfonycasts_tailwind', [
                        'input_css' => ['%kernel.project_dir%/vendor/aequation/wire/assets/styles/wire.css'],
                        // 'output_css' => 'assets/aequation/wire/styles/wire.css',
                        // 'output_dir' => 'assets/aequation/wire/styles',
                        // 'purge_css' => [
                        //     'paths' => [
                        //         '%kernel.project_dir%/templates',
                        //         '%kernel.project_dir%/vendor/aequation/wire/templates',
                        //     ],
                        //     'whitelist' => [],
                        // ],
                    ]);
                    // dd($container->getExtensionConfig('symfonycasts_tailwind'));
                } else {
                    trigger_error(vsprintf('Error %s line %d: "%s" parameters are not configured directly. Please use preprend mode!', [__METHOD__, __LINE__, $name]), E_USER_WARNING);
                    // $container->setParameter('symfonycasts_tailwind.input_css', $input_css);
                }
                break;
            case 'VichUploader':
                $added_mappings = static::getAddedVichMappings();
                $origin_mappings = $container->hasParameter('vich_uploader.mappings') ? $container->getParameter('vich_uploader.mappings') : [];
                foreach ($added_mappings as $name => $values) {
                    $origin_mappings[$name] ??= $values;
                }
                if($asPrepend) {
                    $container->prependExtensionConfig('vich_uploader', [
                        'metadata' => ['type' => 'attribute'],
                        'mappings' => $origin_mappings,
                    ]);
                } else {
                    $container->setParameter('vich_uploader.mappings', $origin_mappings);
                    $container->setParameter('vich_uploader.metadata', ['type' => 'attribute']);
                }
                break;
            case 'AssetMapper':
                // trigger_error(vsprintf('NOT SUPPORTED YET %s line %d: this option is not available (%s) for now.', [__METHOD__, __LINE__, $asPrepend ? 'loaded as PREPREND' : 'loaded regularly']), E_USER_ERROR);
                if(static::isAssetMapperAvailable($container)) {
                    if($asPrepend) {
                        $container->prependExtensionConfig('framework', [
                            'asset_mapper' => [
                                'paths' => [__DIR__.'/../../assets' => '@aequation/wire-bundle'],
                                'excluded_patterns' => [
                                    '*/*_old.*',
                                    'dist/*',
                                ],
                            ]
                        ]);
                        // $container->prependExtensionConfig('framework', [
                        //     'asset_mapper' => [
                        //         'paths' => [__DIR__.'/../../assets/dist' => '@aequation/wire'],
                        //         'excluded_patterns' => [
                        //             '*/*_old.*',
                        //         ],
                        //     ]
                        // ]);
                        // if($asPrepend) {
                        //     $config = [
                        //         'asset_mapper' => [
                        //             'paths' => static::getAssetMapperPaths(),
                        //             'excluded_patterns' => [
                        //                 '*/*_old.*'
                        //             ]
                        //         ],
                        //     ];
                        //     $container->prependExtensionConfig('framework', $config);
                            // dump($config, $container->getExtensionConfig('framework'));
                    } else {
                        trigger_error(vsprintf('Error %s line %d: "%s" parameters are not configured directly. Please use preprend mode!', [__METHOD__, __LINE__, $name]), E_USER_ERROR);
                        throw new Exception(vsprintf('Error %s line %d: "%s" parameters are not configured directly. Please use preprend mode!', [__METHOD__, __LINE__, $name]));
                    }
                } else {
                    trigger_error(vsprintf('Error %s line %d: "%s" parameters are not configured because AssetMapperInterface is not available!', [__METHOD__, __LINE__, $name]), E_USER_ERROR);
                    throw new Exception(vsprintf('Error %s line %d: "%s" parameters are not configured because AssetMapperInterface is not available!', [__METHOD__, __LINE__, $name]));
                }
                break;
            default:
                throw new Exception(vsprintf('Error %s line %d: "%s" parameters are not configured!', [__METHOD__, __LINE__, $name]));
                break;
        }
        // dd($container->getParameterBag()->all());
    }

    private static function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if(interface_exists(AssetMapperInterface::class)) {
            // check that FrameworkBundle 6.3 or higher is installed
            $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
            if(isset($bundlesMetadata['FrameworkBundle'])) {
                $file = $bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php';
                return is_file($file);
            }
        }
        trigger_error(vsprintf('AssetMapperInterface not available %s line %d: this option is not available for now.', [__METHOD__, __LINE__]), E_USER_ERROR);
        return false;
    }

    private static function getParameters(): array
    {
        return [
            'locale' => '%env(APP_LOCALE)%',
            'locales' => ['%env(APP_LOCALE)%'],
            'currency' => '%env(APP_CURRENCY)%',
            'timezone' => '%env(APP_TIMEZONE)%',        
            'vich_dirs' => [
                'item_photo' => '/uploads/item/photo',
                'user_portrait' => '/uploads/user/portrait',
                'slider_slides' => '/uploads/slider/slides',
                'pdf' => '/uploads/pdf',
            ],
            'main_sadmin' => 'manu7772@gmail.com',
        ];
    }

    private static function getFrameworkConfig(): array
    {
        return [
            'csrf_protection' => true,
        ];
    }

    private static function getTwigConfig(): array
    {
        return [
            'file_name_pattern' => '*.twig',
            'form_themes' => [
                'tailwind_2_layout.html.twig',
                '@AequationWire/form/tailwind_wire_layout.html.twig',
            ],
            'globals' => [
                'app' => '@Aequation\WireBundle\Service\interface\AppWireServiceInterface'
            ],
        ];
    }

    private static function getLiipImagineConfig(): array
    {
        return [
            'driver' => "gd",
            'twig' => [
                'mode' => 'lazy',
            ],
        ];
    }

    private static function getAddedVichMappings(): array
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

    // private static function getAssetMapperPaths(): array
    // {
    //     $paths = [
    //         // 'assets/' => 'assets/',
    //         __DIR__.'/../../assets' => '@aequation/wire-bundle',
    //         __DIR__.'/../../assets/dist' => '@aequation/wire',
    //     ];
    //     return $paths;
    // }

}