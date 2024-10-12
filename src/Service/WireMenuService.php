<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireMenu;
use Aequation\WireBundle\Service\interface\WireMenuServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

// #[AsAlias(WireMenuServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WireMenuService extends WireEcollectionService implements WireMenuServiceInterface
{

    // public const ENTITY_CLASS = WireMenu::class;

}