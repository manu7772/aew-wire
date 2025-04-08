<?php
namespace Aequation\WireBundle\Service\interface;

// Symfony

use Aequation\WireBundle\Entity\interface\WireLanguageInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\LocaleSwitcher;
// PHP
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Stopwatch\Stopwatch;
use UnitEnum;
use Twig\Loader\LoaderInterface;
use Twig\Environment;
use Twig\Markup;

interface AppWireServiceInterface extends JsonSerializable, WireServiceInterface
{

    public const DEFAULT_HOME_ROUTE = 'app_home';
    public const SELF_SERIALIZE_GROUPS = ['identifier','for_session'];
    public const UNSERIALIZE_PROPERTIES = [
        'darkmode' => true,
        'timezone' => true,
        'datenow' => true,
        'tinyvalues' => 'mergeTinyvalues',
    ];
    public const SECONDARY_PATHS_PATTERN = '#^\\/(_(profiler|wdt)|css|images|js|assets)\\/#';
    public const APP_WIRE_SESSION_PREFIX = 'appwire_';
    public const STOPWATCH_MAIN_NAME = "stw_main";
    public const DEFAULT_TIMEZONE = 'Europe/Paris';
    public const DEFAULT_DATENOW = 'NOW';
    public const DEFAULT_TINY_VALUES = [];
    public const PUBLIC_FIREWALLS = ['main'];
    public const EXCLUDED_FIREWALLS = ['dev','tmp','image_resolver','uploads','secured_area'];
    // public const EXCLUDED_FIREWALLS_FOR_INIT = ['tmp','image_resolver','uploads','secured_area'];
    public const TEMP_DIR = 'tmp';

    // AppVariable
    public function setTokenStorage(TokenStorageInterface $tokenStorage): void;
    public function setRequestStack(RequestStack $requestStack): void;
    public function setEnvironment(string $environment): void;
    public function setDebug(bool $debug): void;
    public function setLocaleSwitcher(LocaleSwitcher $localeSwitcher): void;
    public function setEnabledLocales(array $enabledLocales): void;
    public function getToken(): ?TokenInterface;
    public function getUser(): ?UserInterface;
    public function getUserService(): WireUserServiceInterface;
    public function getRequest(): ?Request;
    public function getSession(): ?SessionInterface;
    public function getEnvironment(): string;
    public function getDebug(): bool;
    public function getLocale(): string;
    public function getEnabled_locales(): array;
    public function getFlashes(string|array|null $types = null): array;
    public function getCurrent_route(): ?string;
    public function getCurrent_route_parameters(): array;

    public function getContainer(): ContainerInterface;
    public function has(string $id): bool;
    public function get(string $id, int $invalidBehavior = ContainerInterface::NULL_ON_INVALID_REFERENCE): ?object;
    public function getClassService(string|object $objectOrClass): ?object;
    // Languages/locales
    public function getCurrentLocale(): string;
    public function getCurrentLanguage(): ?WireLanguageInterface;
    // Initialized
    public function isInitializable(KernelEvent $event): bool;
    public function isRequiredInitialization(KernelEvent $event): bool;
    public function initialize(KernelEvent $event): bool;
    public function isInitialized(): bool;
    public function integrateUserContext(WireUserInterface $user): void;
    public function saveAppWire(KernelEvent $event): bool;
    public function clearAppWire(?string $firewall = null): bool;
    public function resetAppWire(KernelEvent $event): bool;
    // Request / Session
    public function isXmlHttpRequest(): bool;
    public function isTurboFrameRequest(): bool;
    public function isTurboStreamRequest(bool $prepareRequest = false): bool;
    public function getTurboMetas(bool $asMarkup = true): string|Markup;
    public function getContext(): RequestContext;
    public function getContextAsArray(): array;
    // Dirs
    public function getProjectDir(?string $path = null): string;
    public function getCacheDir(?string $path = null): string;
    public function getLogDir(?string $path = null): string;
    public function getTempDir(?string $path = null): string;
    public function getConfigDir(?string $path = null): string;
    // Parameters
    public function getParameterBag(): ParameterBagInterface;
    public function getParam(string $name, array|bool|string|int|float|UnitEnum|null $default = null): array|bool|string|int|float|UnitEnum|null;
    public function getParameter(string $name, array|bool|string|int|float|UnitEnum|null $default = null): array|bool|string|int|float|UnitEnum|null;
    // Stopwatch
    public function startStopwatch(): static;
    public function getStopwatch(): ?Stopwatch;
    public function getStopwatchTime(): int|float;
    // Tiny values
    public function setTinyvalue(string $name, mixed $value): static;
    public function getTinyvalue(string $name, mixed $default = null): mixed;
    public function setTinyvalues(array $values): static;
    // Serialization
    public function jsonSerialize(): mixed;
    public function jsonUnserialize(array $data): void;
    // Twig
    public function getTwig(): Environment;
    public function getTwigLoader(): LoaderInterface;
    // Darkmode
    public function getDarkmode(): bool;
    public function setDarkmode(?bool $darkmode = null): bool;
    public function getDarkmodeClass(): string;
    // Timestamp
    // Timezone
    public function setTimezone(string|DateTimeZone $timezone): static;
    public function getDefaultTimezone(): DateTimeZone;
    public function getTimezone(): DateTimeZone;
    public function getTimezoneName(): string;
    public function getDatetimeTZ(string|DateTimeImmutable $date = 'now'): DateTimeImmutable;
    // Locale / Languages
    public function runWithLocale(string $locale, callable $callback): static;
    public function resetLocale(): static;
    // DateTime
    public function getCurrentDatetime(): DateTimeImmutable;
    public function getCurrentDatetimeFormated(string $format = DATE_ATOM): string;
    public function getCurrentYear(): string;
    // Environment / Security
    public function isGranted(mixed $attributes, mixed $subject = null): bool;
    public function isUserGranted(?UserInterface $user, $attributes, $object = null, ?string $firewallName = null): bool;
    public function isPublic(): bool;
    public function isPrivate(): bool;
    public function isDev(): bool;
    public function isProd(): bool;
    public function isTest(): bool;
    public function getFirewalls(): array;
    public function getFirewallName(): ?string;
    public function getPublicFirewalls(): array;
    public function getMainFirewalls(): array;
    public function getFirewallChoices(bool $onlyMains = true): array;
    public function isMainFirewall(): bool;
    // Routes
    public function getRoutes(): RouteCollection;
    public function getPublicHomeRoute(): string;
    public function routeExists(string $route, bool|array $control_generation = false): bool;
    public function getUrlIfExists(string $route, array $parameters = [], ?int $referenceType = null, null|array|string $methods = null): ?string;

}