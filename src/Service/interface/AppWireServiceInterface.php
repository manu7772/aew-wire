<?php
namespace Aequation\WireBundle\Service\interface;

// Symfony
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
// PHP
use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use UnitEnum;

interface AppWireServiceInterface extends JsonSerializable, WireServiceInterface
{

    public const CONTEXT_SESSNAME = 'app_context';
    public const PUBLIC_FIREWALLS = ['main'];
    public const EXCLUDED_FIREWALLS = ['dev','tmp','image_resolver','uploads','secured_area'];

    public function getContainer(): ContainerInterface;
    public function has(string $id): bool;
    public function get(string $id, int $invalidBehavior = ContainerInterface::NULL_ON_INVALID_REFERENCE): ?object;
    public function getClassService(string|object $objectOrClass): ?object;
    // Request / Session
    public function getRequest(): ?Request;
    public function isXmlHttpRequest(?Request $request = null): bool;
    // Parameters
    public function getParameterBag(): ParameterBagInterface;
    public function getParam(
        string $name,
        array|bool|string|int|float|UnitEnum|null $default = null,
    ): array|bool|string|int|float|UnitEnum|null;
    public function getParameter(
        string $name,
        array|bool|string|int|float|UnitEnum|null $default = null,
    ): array|bool|string|int|float|UnitEnum|null;
    // Timezone
    public function getDefaultTimezone(): DateTimeZone;
    public function getTimezone(): DateTimeZone;
    // DateTime
    public function getCurrentDatetime(): DateTimeImmutable;
    public function getCurrentDatetimeFormated(string $format = DATE_ATOM): string;
    // Environment / Security
    public function getUser(): ?UserInterface;
    public function isPublic(): bool;
    public function isPrivate(): bool;
    public function isDev(): bool;
    public function isProd(): bool;
    public function isTest(): bool;
    public function getEnvironment(): string;
    public function getFirewalls(): array;
    public function getMainFirewalls(): array;
    public function getFirewallChoices(bool $onlyMains = true): array;

}