<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\WireEcollectionServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

// #[AsAlias(WireEcollectionServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WireEcollectionService extends WireItemService implements WireEcollectionServiceInterface
{
    // public const ENTITY_CLASS = WireEcollection::class;

}