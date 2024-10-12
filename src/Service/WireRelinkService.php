<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\WireRelinkServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

// #[AsAlias(WireRelinkServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WireRelinkService extends WireItemService implements WireRelinkServiceInterface
{

}