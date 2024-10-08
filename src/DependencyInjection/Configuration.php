<?php
namespace Aequation\WireBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    // private bool $isDev = false;

    // public function __construct(
    //     private array $config,
    //     private ContainerBuilder $container,
    // )
    // {
    //     $this->isDev = strtolower($container->getParameter('kernel.environment')) === 'dev';
    // }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('aequation_wire');
        return $treeBuilder;
    }
}