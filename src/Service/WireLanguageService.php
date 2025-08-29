<?php
namespace Aequation\WireBundle\Service;

use Locale;
use DateTimeZone;
use Knp\Component\Pager\PaginatorInterface;
use Aequation\WireBundle\Component\Opresult;
use Symfony\Component\HttpFoundation\Request;
use Aequation\WireBundle\Repository\BaseWireRepository;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Repository\WireLanguageRepository;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
// Symfony
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\PaginatedContextDataInterface;
use Aequation\WireBundle\Component\PaginatedContextData;
use Aequation\WireBundle\Entity\interface\WireLanguageInterface;
use Aequation\WireBundle\Form\WireLanguageType;
// PHP
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireLanguageServiceInterface;

/**
 * Language/Locale service
 * 
 * @see https://www.php.net/manual/fr/class.locale.php
 * Sandbox
 * @see https://onlinephp.io/c/9b1d0
 */
class WireLanguageService implements WireLanguageServiceInterface
{
    use TraitBaseService;
    use TraitBaseEntityService;

    public const ENTITY_CLASS = WireLanguageInterface::class;
    public const ENTITY_TYPE = WireLanguageType::class;

    public const LOCALES_TIMEZONES = [
        'fr' => 'Europe/Paris',
        'it' => 'Europe/Rome',
        'en' => 'Europe/London',
        'de' => 'Europe/Berlin',
        'es' => 'Europe/Madrid',
        'ru' => 'Europe/Moscow',
        'us' => 'America/New_York',
        'ja' => 'Asia/Tokyo',
        'zh' => 'Asia/Shanghai',
        // Add more mappings as needed
    ];
    public const DEFAULT_CHECK_DB_OPTIONS = [
        'flush_one_by_one' => false,
        'load_all_if_less_or_equal_than' => 1000, // If the number of entities is less or equal than this value, all entities will be loaded in one query
    ];

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEm,
        protected PaginatorInterface $paginator,
    ) {
    }

    public static function getTimezoneRegions(): array
    {
        return [
            DateTimeZone::AFRICA,
            DateTimeZone::AMERICA,
            DateTimeZone::ANTARCTICA,
            DateTimeZone::ARCTIC,
            DateTimeZone::ASIA,
            DateTimeZone::ATLANTIC,
            DateTimeZone::AUSTRALIA,
            DateTimeZone::EUROPE,
            DateTimeZone::INDIAN,
            DateTimeZone::PACIFIC,
            // DateTimeZone::UTC,
            // DateTimeZone::ALL,
            // DateTimeZone::ALL_WITH_BC,
            // DateTimeZone::PER_COUNTRY,
        ];
    }


    /**
     * Returns the locale from the browser's Accept-Language header.
     * If no locale is found, it returns the default locale or the preferred language.
     * @see https://stackoverflow.com/questions/3770513/detect-browser-language-in-php
     *
     * @param string|null $defaultLocale
     * @return string
     */
    public function getBrowserLocale(
        ?string $defaultLocale = null
    ): string
    {
        $locale = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if(strlen($locale) === 2) {
            return $locale;
        }
        return $this->getPreferedLanguage()?->getLocale() ?? $defaultLocale;
    }

    public static function getTimezoneChoices(): array
    {
        $choices = [];
        foreach (DateTimeZone::listIdentifiers() as $name) {
            $choices[$name] = $name;
        }
        return $choices;
    }

    /**
     * Sets the current locale.
     *
     * @return void
     */
    public function setLocale(string $locale)
    {
        $this->appWire->setLocale($locale);
    }

    /**
     * Returns the current locale.
     */
    public function getLocale(): string
    {
        return $this->appWire->getLocale();
    }

    public static function getAvailableLocales(): array
    {
        return array_values(static::getPrimaryLanguages());
    }

    public static function getLocales(): array
    {
        return array_keys(static::LOCALES_TIMEZONES);
    }

    public static function isValidLocale(string $locale): bool
    {
        return in_array($locale, static::getAvailableLocales());
    }

    public static function getPrimaryLanguages(): array
    {
        $locales = static::getLocales();
        return array_map(
            static function ($locale) {
                return static::getPrimaryLanguage($locale);
            },
            $locales
        );
    }

    public static function getPrimaryLanguage(string $locale): string
    {
        return Locale::getPrimaryLanguage($locale);
    }

    public static function getRegions(): array
    {
        $locales = static::getLocales();
        return array_map(
            static function ($locale) {
                return static::getRegion($locale);
            },
            $locales
        );
    }

    public static function getRegion(string $locale): string
    {
        return Locale::getRegion($locale);
    }

    public static function getLocaleName(string $locale, ?string $language = null): string
    {
        return Locale::getDisplayLanguage($locale, static::getPrimaryLanguage($language ?? $locale));
    }

    public static function findTimezoneByLocale(string $locale): string
    {
        return static::LOCALES_TIMEZONES[$locale] ?? 'UTC';
    }

    public function findLanguageByLocale(string $locale): ?WireLanguageInterface
    {
        return $this->getRepository()->findOneBy(['locale' => $locale]);
    }

    public function getPreferedLanguage(): ?WireLanguageInterface
    {
        return $this->getRepository()->findOneBy(['prefered' => true]);
    }

    public function getLanguages(bool $onlyActive = false): array
    {
        $criteria = $onlyActive ? ['enabled' => true] : [];
        return $this->getRepository()->findBy($criteria, ['locale' => 'ASC']);
    }

    public function getLanguageChoices(bool $onlyActive = false): array
    {
        $choices = [];
        foreach ($this->getLanguages($onlyActive) as $entity) {
            $choices[$entity->getLocale()] = $entity->getId();
        }
        return $choices;
    }

    public function getTimezones(): array
    {
        return array_values(static::LOCALES_TIMEZONES);
    }

    public function getLanguageLocaleChoices(): array
    {
        $locales = static::getLocales();
        return array_combine($locales, $locales);
    }


    /****************************************************************************************************/
    /** PAGINABLE                                                                                       */
    /****************************************************************************************************/

    /**
     * Get paginated context data.
     *
     * @param Request $request
     * @return array
     */
    public function getPaginatedContextData(array $options = []): PaginatedContextDataInterface
    {
        $options = [
            'fields' => [
                'id' => [
                    'classes' => ['text-center','w-0'],
                    'sortable' => true,
                ],
                'locale' => [
                    'classes' => ['text-center','w-0'],
                    'sortable' => true,
                ],
                'name' => [
                    'classes' => ['text-center','w-0'],
                    'sortable' => true,
                ],
                'timezone' => [
                    'classes' => ['text-center','w-0'],
                    'sortable' => true,
                ],
            ],
        ];
        return new PaginatedContextData($this, $options);
    }

}