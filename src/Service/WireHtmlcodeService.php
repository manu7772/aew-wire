<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireHtmlcode;
use Aequation\WireBundle\Service\interface\WireHtmlcodeServiceInterface;
use SplFileInfo;
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
    public const TWIGFILE_MATCH = '/(\.html?)\.twig$/i';

    protected function getNewFinder(): Finder
    {
        return Finder::create()
            ->ignoreUnreadableDirs()
            ->ignoreDotFiles(true)
            ->followLinks()
            ;
    }

    public static function stripTwigfile(string|SplFileInfo $twigfile, bool $removeExtension = false): string
    {
        $basename = $twigfile instanceof SplFileInfo
            ? $twigfile->getFilename()
            : pathinfo($twigfile)['basename'];
        return $removeExtension
            ? preg_replace(static::TWIGFILE_MATCH, '', $basename)
            : $basename;
    }

}