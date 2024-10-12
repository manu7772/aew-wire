<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireHtmlcode;
use Aequation\WireBundle\Service\interface\WireHtmlcodeServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

// #[AsAlias(WireHtmlcodeServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WireHtmlcodeService extends WireEcollectionService implements WireHtmlcodeServiceInterface
{

    // public const ENTITY_CLASS = WireHtmlcode::class;

}