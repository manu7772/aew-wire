<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\WireItemServiceInterface;
// // Symfony
// use Symfony\Component\DependencyInjection\Attribute\AsAlias;
// use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

// #[AsAlias(WireItemServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WireItemService extends BaseWireEntityService implements WireItemServiceInterface
{

    // public const ENTITY_CLASS = WireItem::class;

}