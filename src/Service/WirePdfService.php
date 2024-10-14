<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\WirePdfServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

// #[AsAlias(WirePdfServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WirePdfService extends WireItemService implements WirePdfServiceInterface
{
    // public const ENTITY_CLASS = WirePdf::class;

}