<?php
namespace Aequation\WireBundle\Service\interface;

interface InitializerInterface extends WireServiceInterface
{

    public const MAIN_PATH = "initialization";
    public const SOURCES = [
        'manage_entities' => [
            "entities" => "metadata_entities.yaml",
            "options" => "options_entities.yaml",
        ],
        'copy_yaml_files' => "copy_yaml_files.yaml",
    ];
    public const SEARCH_DEPTH = ['>=0','<3'];

    public function installConfig(string $name): bool;
    public function getConfigNames(): array;
    public function hasConfigName(string $name): bool;
    public function findConfigFiles(string $name): null|string|array;
    public function getConfigData(string $name): ?array;
    public function getAllConfigsData(): array;

}