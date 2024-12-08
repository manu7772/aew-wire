<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\OpresultInterface;

interface InitializerInterface extends WireServiceInterface
{

    public const MAIN_PATH = "initialization";
    public const SOURCES = [
        'textfiles_actions' => "textfiles_actions.yaml",
        'insert_yaml_configs' => "insert_yaml_configs.yaml",
        'copy_config_files' => "copy_config_files.yaml",
        'manage_entities' => [
            "entities" => "metadata_entities.yaml",
            "options" => "options_entities.yaml",
        ],
    ];
    public const SEARCH_DEPTH = ['>=0','<3'];

    public function installConfig(string $name): OpresultInterface;
    public function getConfigNames(): array;
    public function hasConfigName(string $name): bool;
    public function findConfigFiles(string $name): array;
    public function getConfigData(string $name): ?array;
    // public function getAllConfigsData(): array;

}