<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireCategory;
use Aequation\WireBundle\Service\interface\WireCategoryServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

// #[AsAlias(WireCategoryServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WireCategoryService extends BaseWireEntityService implements WireCategoryServiceInterface
{
    // public const ENTITY_CLASS = WireCategory::class;

    public function getCategoryTypeChoices(): array
    {
        $choices = [];
        
        return $choices;
    }

}