<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\WireLanguageInterface;

interface WireLanguageServiceInterface extends WireEntityServiceInterface
{

    public function setLocale(string $locale);
    public function getLocale(): string;
    public static function getTimezoneRegions(): array;
    public static function getTimezoneChoices(): array;
    public static function getLocales(): array;
    public static function getPrimaryLanguage(string $locale): string;
    public static function getRegion(string $locale): string;
    public static function getLocaleName(string $locale, string $language): string;
    public static function findTimezoneByLocale(string $locale): string;
    public function findLanguageByLocale(string $locale): ?WireLanguageInterface;
    public function getPreferedLanguage(): ?WireLanguageInterface;
    public function getLanguages(bool $onlyActive = false): array;
    public function getLanguageChoices(bool $onlyActive = false): array;
    public function getLanguageLocaleChoices(): array;

}