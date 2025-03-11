<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

interface ObjectHydratorInterface
{
    public function getAppWire(): AppWireServiceInterface;
    public function getWireEntityManager(): WireEntityManagerInterface;
    public function findPathYamlFiles(string $path): array|false;
    public function getPathYamlData(string $path): array|false;
    public function getYamlData(string $file): array|false;
    public function generateEntitiesFromClass(string $classname, bool $replace = false, ?SymfonyStyle $io = null): OpresultInterface;
    public function generateEntitiesFromFile(string $filename, bool $replace = false, ?SymfonyStyle $io = null): OpresultInterface;
    public function generateEntities($classname, array $items, bool $replace = false, ?SymfonyStyle $io = null): OpresultInterface;
}