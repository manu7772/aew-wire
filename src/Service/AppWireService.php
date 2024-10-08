<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\AttributeWireServiceInterface;
use Aequation\WireBundle\Tools\HttpRequest;
// Symphony
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Loader\LoaderInterface;
use Twig\Environment;
// PHP
use DateTimeImmutable;
use DateTimeZone;
use BadMethodCallException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use UnitEnum;

#[AsAlias(AppWireServiceInterface::class, public: false)]
#[Autoconfigure(autowire: true, lazy: false)]
class AppWireService extends BaseService implements AppWireServiceInterface
{

    public const DEFAULT_TIMEZONE = 'Europe/Paris';
    public const DEFAULT_DATENOW = 'NOW';

    public readonly ContainerInterface $container;

    // Serializable data
    protected int $timestamp;
    protected DateTimeZone $timezone;
    protected string $datenow;
    protected string $firewallname;

    public function __construct(
        public readonly KernelInterface $kernel,
        public readonly Security $security,
        public readonly RequestStack $requestStack,
        public readonly Environment $twig,
        public readonly ParameterBagInterface $parameterBag,

        // public readonly AccessDecisionManagerInterface $accessDecisionManager,
        // public readonly NormalizerInterface $normalizer,
    ) {
        $this->container = $this->kernel->getContainer();
        $this->initializeAppContext();
    }


    /************************************************************************************************************/
    /** CONTAINER / SERVICES                                                                                    */
    /************************************************************************************************************/

    public function getContainer(): ContainerInterface
    {
        return $this->container ??= $this->kernel->getContainer();
    }

    /**
     * Has service (only if public)
     * @param string $id
     * @return bool
     */
    public function has(
        string $id
    ): bool
    {
        return $this->getContainer()->has($id);
        // return $this->getContainer()?->has($id) ?: false;
    }

    /**
     * Get service (only if public)
     * @param string $id
     * @param [type] $invalidBehavior
     * @return object|null
     */
    public function get(
        string $id,
        int $invalidBehavior = ContainerInterface::NULL_ON_INVALID_REFERENCE
    ): ?object
    {
        return $this->getContainer()->get($id, $invalidBehavior);
    }

    public function getClassService(
        string|object $objectOrClass
    ): ?object
    {
        $serviceName = $this->get(AttributeWireServiceInterface::class)->getClassServiceName($objectOrClass);
        return !empty($serviceName) && $this->has($serviceName)
            ? $this->get($serviceName)
            : null;
    }


    /************************************************************************************************************/
    /** PARAMETERS                                                                                              */
    /************************************************************************************************************/

    public function getParameterBag(): ParameterBagInterface
    {
        return $this->parameterBag;
    }

    public function getParam(
        string $name,
        array|bool|string|int|float|UnitEnum|null $default = null,
    ): array|bool|string|int|float|UnitEnum|null
    {
        return $this->getParameter($name, $default);
    }

    public function getParameter(
        string $name,
        array|bool|string|int|float|UnitEnum|null $default = null,
    ): array|bool|string|int|float|UnitEnum|null
    {
        if($this->parameterBag->has($name)) {
            try {
                return $this->parameterBag->get($name);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        return $default;
    }


    /************************************************************************************************************/
    /** REQUEST / SESSION                                                                                       */
    /************************************************************************************************************/

    public function getRequest(): ?Request
    {
        return $this->requestStack->getMainRequest();
    }

    public function isXmlHttpRequest(
        ?Request $request = null
    ): bool
    {
        $request ??= $this->getRequest();
        if(empty($request)) return false;
        return $request->headers->get('x-requested-with', null) === 'XMLHttpRequest';
    }


    /************************************************************************************************************/
    /** APP CONTEXT                                                                                             */
    /************************************************************************************************************/

    protected function initializeAppContext(): void
    {
        $this->timestamp = time();
    }

    /** TIMEZONE */

    public function getDefaultTimezone(): DateTimeZone
    {
        $timezone = $this->container->hasParameter('timezone') ? $this->container->getParameter('timezone') : static::DEFAULT_TIMEZONE;
        return new DateTimeZone($timezone);
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->timezone ?? $this->getDefaultTimezone();
    }

    public function getTimezoneAsString(): string
    {
        return $this->getTimezone()->getName();
    }

    public function setTimezone(
        string|DateTimeZone $timezone
    ): static
    {
        $this->timezone = is_string($timezone) ? new DateTimeZone($timezone) : $timezone;
        return $this;
    }

    /** DATENOW */

    public function getDefaultDatenow(): string
    {
        return $this->container->hasParameter('datenow') ? $this->container->getParameter('datenow') : static::DEFAULT_DATENOW;
    }

    public function getDatenow(): string
    {
        return $this->datenow ?? $this->getDefaultDatenow();
    }

    public function setDatenow(
        string $datenow
    ): static
    {
        if($this->isDev()) $test = new DateTimeImmutable($datenow);
        $this->datenow = $datenow;
        return $this;
    }

    public function getCurrentDatetime(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->datenow);
    }

    public function getCurrentDatetimeFormated(
        string $format = DATE_ATOM
    ): string
    {
        $date = new DateTimeImmutable($this->datenow);
        return $date->format($format);
    }


    /** SERIALIZE */

    protected function getSerializables(): array
    {
        return [
            'environment' => 'getEnvironment',
            'firewallname' => 'getFirewallName',
            'public' => 'isPublic',
            'private' => 'isPrivate',
            'timezone' => 'getTimezoneAsString',
            'datenow' => null,
            'currentdatetime' => 'getCurrentDatetimeFormated',
        ];
    }

    public function jsonSerialize(): mixed
    {
        $data = [];
        foreach ($this->getSerializables() as $property => $getter) {
            $data[$property] = !empty($getter) ? $this->$getter() : $this->$getter;
        }
        return $data;
    }

    // public function __sleep(): array
    // {
    //     throw new BadMethodCallException(vsprintf('Cannot serialize %s', [static::class.(static::class !== __CLASS__ ? PHP_EOL.'(based on '.__CLASS__.')' : '')]));
    // }

    // public function __wakeup(): void
    // {
    //     throw new BadMethodCallException(vsprintf('Cannot unserialize %s', [static::class.(static::class !== __CLASS__ ? PHP_EOL.'(based on '.__CLASS__.')' : '')]));
    // }

    /************************************************************************************************************/
    /** TWIG                                                                                                    */
    /************************************************************************************************************/

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function getTwigLoader(): LoaderInterface
    {
        return $this->twig->getLoader();
    }


    /************************************************************************************************************/
    /** SECURITY                                                                                                */
    /************************************************************************************************************/

    public function getUser(): ?UserInterface
    {
        return $this->security->getUser();
    }

    public function isPublic(): bool
    {
        return in_array(strtolower($this->getFirewallName()), static::PUBLIC_FIREWALLS);
    }

    public function isPrivate(): bool
    {
        return !$this->isPublic();
    }

    public static function isCli(): bool
    {
        return HttpRequest::isCli();
    }

    public function isDev(): bool
    {
        return $this->kernel->getEnvironment() === 'dev';
    }

    public function isProd(): bool
    {
        return $this->kernel->getEnvironment() === 'prod';
    }

    public function isTest(): bool
    {
        return $this->kernel->getEnvironment() === 'test';
    }

    public function getEnvironment(): string
    {
        return $this->kernel->getEnvironment();
    }

    public function getFirewallConfig(): ?FirewallConfig
    {
        return $this->getRequest() ? $this->security->getFirewallConfig($this->getRequest()) : null;
    }

    public function getFirewallName(): ?string
    {
        if(!isset($this->firewallname)) {
            $fwc = $this->getFirewallConfig();
            return $fwc
                ? $this->firewallname = $fwc->getName()
                : null;
        }
        return $this->firewallname;
    }

    public function getFirewalls(): array
    {
        return $this->container->getParameter('security.firewalls');
    }

    public function getMainFirewalls(): array
    {
        $firewalls = $this->getFirewalls();
        return array_filter($firewalls, fn($fw) => !in_array($fw, static::EXCLUDED_FIREWALLS));
    }

    public function getFirewallChoices(
        bool $onlyMains = true,
    ): array
    {
        $firewalls = $onlyMains
            ? $this->getMainFirewalls()
            : $this->getFirewalls();
        return array_combine($firewalls, $firewalls);
    }


    /************************************************************************************************************/
    /** CACHE                                                                                                   */
    /************************************************************************************************************/

    // public function getCache(): CacheServiceInterface
    // {
    //     return $this->get(CacheServiceInterface::class);
    // }

}