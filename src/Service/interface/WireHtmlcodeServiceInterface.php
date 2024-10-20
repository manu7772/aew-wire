<?php
namespace Aequation\WireBundle\Service\interface;

use SplFileInfo;

interface WireHtmlcodeServiceInterface extends WireEcollectionServiceInterface
{

    public static function stripTwigfile(string|SplFileInfo $twigfile, bool $removeExtension = false): string;

}