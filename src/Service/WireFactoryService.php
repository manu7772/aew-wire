<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\WireFactoryServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

// #[AsAlias(WireFactoryServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WireFactoryService extends WireItemService implements WireFactoryServiceInterface
{
    
}