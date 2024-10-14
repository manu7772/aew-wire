<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireHtmlcode;
use Aequation\WireBundle\Service\interface\WireHtmlcodeServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Finder\Finder;

// #[AsAlias(WireHtmlcodeServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WireHtmlcodeService extends WireEcollectionService implements WireHtmlcodeServiceInterface
{

    // public const ENTITY_CLASS = WireHtmlcode::class;
    public const SEARCH_FILES_DEPTH = ['>=0','<2'];

    protected function getNewFinder(): Finder
    {
        return Finder::create()
            ->ignoreUnreadableDirs()
            ->ignoreDotFiles(true)
            ->followLinks()
            ;
    }


}