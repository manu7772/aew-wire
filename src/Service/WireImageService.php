<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\WireImageServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

// #[AsAlias(WireImageServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WireImageService extends WireItemService implements WireImageServiceInterface
{
    // public const ENTITY_CLASS = WireImage::class;

}