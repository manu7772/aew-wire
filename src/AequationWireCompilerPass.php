<?php
namespace Aequation\WireBundle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Throwable;

class AequationWireCompilerPass implements CompilerPassInterface
{
    public const DATABASE_PARAMETERS = false;
    public const EXECUTE_DISPATCH = true;
    public const REMOVE_DISPATCHEDS = true;

    public function Process(
        ContainerBuilder $container
    ): void
    {
        // if($container->hasParameter('main_sadmin')) {
        //     // $resources = $container->getParameter('main_sadmin') ?: [] ;
        //     // array_unshift($resources, '@AequationWire/form/wire_app_layout.html.twig');
        //     $container->setParameter('main_sadmin', 'test@test.com');
        // }

        $added_mappings = [
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

        // Add Vich mappings
        $mappings = $container->hasParameter('vich_uploader.mappings') ? $container->getParameter('vich_uploader.mappings') : [];
        if(!is_array($mappings)) $mappings = [];
        foreach ($added_mappings as $name => $value) {
            $mappings[$name] ??= $value;
        }
        // Add Tailwindcss input_css
        $container->setParameter('vich_uploader.mappings', $mappings);
        $container->setParameter('vich_uploader.metadata', ['type' => 'attribute']);
        // dd($container->getParameter('vich_uploader.mappings'));
        $input_css = $container->hasParameter('symfonycasts_tailwind.input_css') ? $container->getParameter('symfonycasts_tailwind.input_css') : [];
        $input_css = array_unique(array_merge(['assets/@aequation/wire/styles/wire.css'], $input_css));
        $container->setParameter('symfonycasts_tailwind.input_css', $input_css);

        if(static::DATABASE_PARAMETERS) {
            $siteparams = $this->getParams();
            foreach ($siteparams as $param) {
                $container->setParameter($param['name'], $param['paramvalue']);
            }
        }
    }

    private function getParams(): array
    {
        /** @var Connection $connexion */
        $connexion = DriverManager::getConnection(['url' => $_ENV['DATABASE_URL']]);
        try {
            $database_params = $connexion->executeQuery('SELECT P.name, P.paramvalue, P.dispatch FROM siteparams as P')->fetchAllAssociative();
        } catch (Throwable $th) {
            $database_params = false;
        }
        if(empty($database_params)) return [];
        $params = array_map(
            function($param) {
                $param['paramvalue'] = json_decode($param['paramvalue'], true);
                return $param;
            },
            $database_params
        );
        if(static::EXECUTE_DISPATCH) {
            foreach ($params as $idx => $param) {
                $remove = false;
                if($param['dispatch'] && is_array($param['paramvalue']) && !array_is_list($param['paramvalue'])) {
                    foreach ($param['paramvalue'] as $key => $val) {
                        if(preg_match('/^[\w\d_-]+$/', $key)) {
                            $newid = $param['name'].'.'.$key;
                            $params[] = [
                                'name' => $newid,
                                'paramvalue' => $val,
                            ];
                            $remove = true;
                        }
                    }
                }
                if($remove && static::REMOVE_DISPATCHEDS) unset($params[$idx]);
            }
        }
        return $params;
    }

}