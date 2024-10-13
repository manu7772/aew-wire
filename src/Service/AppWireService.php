<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\AttributeWireServiceInterface;
use Aequation\WireBundle\Service\interface\TimezoneInterface;
use Aequation\WireBundle\Tools\HttpRequest;
use BadMethodCallException;
// Symphony
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\UX\Turbo\TurboBundle;
// PHP
use Twig\Loader\LoaderInterface;
use Twig\Environment;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Serializable;
use Symfony\Component\Stopwatch\Stopwatch;
use UnitEnum;

#[AsAlias(AppWireServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class AppWireService extends AppVariable implements AppWireServiceInterface
{

    public readonly ContainerInterface $container;
    public readonly SessionInterface $session;
    private bool $context_initialized = false;
    private readonly array $symfony;
    private readonly array $php;
    private readonly Stopwatch $stopwatch;
    
    // Serializable data
    private int $timestamp;
    private DateTimeZone $timezone;
    private string $datenow;
    private string $firewallname;
    private array $tinyvalues = [];

    public function __construct(
        public readonly KernelInterface $kernel,
        public readonly Security $security,
        public readonly Environment $twig,
        public readonly ParameterBagInterface $parameterBag,
        RequestStack $requestStack,
        LocaleSwitcher $localeSwitcher,
        TokenStorageInterface $tokenStorage,
        // public readonly AccessDecisionManagerInterface $accessDecisionManager,
        // public readonly NormalizerInterface $normalizer,
    ) {
        // $this->startStopwatch();
        $this->timestamp = time();
        $this->tinyvalues = static::DEFAULT_TINY_VALUES;
        $this->setTokenStorage($tokenStorage);
        $this->setRequestStack($requestStack);
        $this->setEnvironment($kernel->getEnvironment());
        $this->setDebug($kernel->isDebug());
        $this->setLocaleSwitcher($localeSwitcher);
        $this->setEnabledLocales(['fr_FR']);
        $this->container = $this->kernel->getContainer();
    }


    /************************************************************************************************************/
    /** IMPLEMENTS AppWireServiceInterface                                                                      */
    /************************************************************************************************************/

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return static::class;
    }

    public function __sleep(): array
    {
        throw new BadMethodCallException(vsprintf('Cannot serialize %s', [static::class.(static::class !== __CLASS__ ? PHP_EOL.'(based on '.__CLASS__.')' : '')]));
    }

    public function __wakeup(): void
    {
        throw new BadMethodCallException(vsprintf('Cannot unserialize %s', [static::class.(static::class !== __CLASS__ ? PHP_EOL.'(based on '.__CLASS__.')' : '')]));
    }

    /**
     * Returns the current session.
     */
    public function getSession(): ?SessionInterface
    {
        // if (!isset($this->requestStack)) { ???????????????????????????????????
        //     throw new \RuntimeException(vsprintf('Error %s line %d: session is not available or loaded yet.', [__METHOD__, __LINE__]));
        // }
        $request = $this->getRequest();
        if (!$request) {
            throw new \RuntimeException(vsprintf('Error %s line %d: session is not available or loaded yet.', [__METHOD__, __LINE__]));
        }
        $session = $request?->hasSession() ? $request->getSession() : null;
        return $session;
    }


    /************************************************************************************************************/
    /** SYMFONY / PHP info                                                                                      */
    /************************************************************************************************************/

    public function getSymfony(): array
    {
        if(!isset($this->symfony)) {
            /** @var App/Kernel $kernel */
            $kernel = $this->kernel;
            $eom = explode('/', $kernel::END_OF_MAINTENANCE);
            $END_OF_MAINTENANCE = new DateTimeImmutable($eom[1].'-'.$eom[0].'-01');
            $eol = explode('/', $kernel::END_OF_LIFE);
            $END_OF_LIFE = new DateTimeImmutable($eol[1].'-'.$eol[0].'-01');
            $this->symfony = [
                'VERSION' => $kernel::VERSION,
                'SHORT_VERSION' => $kernel::MAJOR_VERSION.'.'.$kernel::MINOR_VERSION,
                'VERSION_ID' => $kernel::VERSION_ID,
                'MAJOR_VERSION' => $kernel::MAJOR_VERSION,
                'MINOR_VERSION' => $kernel::MINOR_VERSION,
                'RELEASE_VERSION' => $kernel::RELEASE_VERSION,
                'EXTRA_VERSION' => $kernel::EXTRA_VERSION,
                'END_OF_MAINTENANCE' => $END_OF_MAINTENANCE,
                'END_OF_MAINTENANCE_TEXT' => $END_OF_MAINTENANCE->format('d/m/Y'),
                'END_OF_LIFE' => $END_OF_LIFE,
                'END_OF_LIFE_TEXT' => $END_OF_LIFE->format('d/m/Y'),
            ];
        }
        return $this->symfony;
    }

    public function getPhp(): array
    {
        if(!isset($this->php)) {
            // PHP INFO / in MB : memory_get_usage() / 1048576
            $this->php = [
                'version' => phpversion(),
                'PHP_VERSION_ID' => PHP_VERSION_ID,
                'PHP_EXTRA_VERSION' => PHP_EXTRA_VERSION,
                'PHP_MAJOR_VERSION' => PHP_MAJOR_VERSION,
                'PHP_MINOR_VERSION' => PHP_MINOR_VERSION,
                'PHP_RELEASE_VERSION' => PHP_RELEASE_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'date.timezone' => ini_get('date.timezone'),
            ];
        }
        return $this->php;
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

    public function isXmlHttpRequest(): bool
    {
        return $this->getRequest()?->headers->get('x-requested-with', null) === 'XMLHttpRequest' ?: false;
    }

    public function isTurboFrameRequest(): bool
    {
        return $this->getRequest()?->headers->has('Turbo-Frame') ?: false;
    }

    public function isTurboStreamRequest(
        bool $prepareRequest = false
    ): bool
    {
        $request = $this->getRequest();
        $isTurbo = $request
            ? $request->getMethod() !== 'GET' && TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()
            : false;
        if($isTurbo && $prepareRequest) $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        return $isTurbo;
    }


    /************************************************************************************************************/
    /** INITIALIZE                                                                                              */
    /************************************************************************************************************/

    public function initialize(): bool
    {
        $this->startStopwatch();
        if(!$this->isInitialized()) {
            $session = $this->getSession();
            if($session instanceof SessionInterface) {
                if(!$this->isCurrentFirewallAvailableForInit()) {
                    $this->context_initialized = false;
                    // if($this->isDev()) throw new Exception(vsprintf('Error %s line %d: initialization is forbidden in this firewall %s!', [__METHOD__, __LINE__, $this->getFirewallName()]));
                } else {
                    $this->session ??= $session;
                    $session_data = $this->retrieveAppWire();
                    $this->jsonUnserialize($session_data);
                    $this->context_initialized = true;
                }    
            }
        }
        return $this->isInitialized();
    }

    public function retrieveAppWire(
        string $firewall = null
    ): ?array
    {
        $firewall ??= $this->getFirewallName();
        return $this->session->get(static::APP_WIRE_SESSION_PREFIX.$firewall, []);
    }

    public function saveAppWire(): bool
    {
        if($this->isInitialized()) {
            $this->session->set(static::APP_WIRE_SESSION_PREFIX.$this->getFirewallName(), $this->jsonSerialize(true));
            if($this->isDev()) dump($this->session->get(static::APP_WIRE_SESSION_PREFIX.$this->getFirewallName()));
            return true;
        }
        return false;
    }

    public function clearAppWire(
        string $firewall = null
    ): bool
    {
        if($this->session ?? false) {
            $firewall ??= $this->getFirewallName();
            if($this->session->has(static::APP_WIRE_SESSION_PREFIX.$firewall)) {
                $this->session->remove(static::APP_WIRE_SESSION_PREFIX.$firewall);
                // if($firewall === $this->getFirewallName()) $this->context_initialized = false;
            }
            return !$this->session->has(static::APP_WIRE_SESSION_PREFIX.$firewall);
        }
        return false;
    }

    public function resetAppWire(): bool
    {
        if($this->clearAppWire()) {
            $this->context_initialized = false;
            return $this->initialize();
        }
        return false;
    }

    public function isInitialized(): bool
    {
        return $this->context_initialized;
    }

    /** TIMEZONE */

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getDefaultTimezone(): DateTimeZone
    {
        $timezone = $this->container->hasParameter('timezone') ? $this->container->getParameter('timezone') : static::DEFAULT_TIMEZONE;
        return new DateTimeZone($timezone);
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->timezone ?? $this->getDefaultTimezone();
    }

    public function getTimezoneName(): string
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
        if($this->isDev()) {
            try {
                $test = new DateTimeImmutable($datenow, $this->getTimezone());
            } catch (\Throwable $th) {
                throw new Exception(vsprintf('Error %s line %d: date %s is not available! (%s)', [__METHOD__, __LINE__, json_encode($datenow), $th->getMessage()]));
            }
        }
        $this->datenow = $datenow;
        return $this;
    }

    /**
     * Get a new DateTime object with the current TimeZone
     * @param string $date
     * @return DateTimeImmutable
     */
    public function getDatetimeTZ(
        string|DateTimeInterface $date = 'now'
    ): DateTimeImmutable
    {
        return $date instanceof DateTimeInterface
            ? $date->setTimezone($this->getTimezone())
            : new DateTimeImmutable($date, $this->getTimezone());
    }

    public function getCurrentDatetime(
        TimezoneInterface $object = null
    ): DateTimeImmutable
    {
        $timezone = $object ? $object->getDateTimezone() : $this->getTimezone();
        return new DateTimeImmutable($this->getDatenow(), $timezone);
    }

    public function getCurrentDatetimeFormated(
        string $format = DATE_ATOM,
        TimezoneInterface $object = null
    ): string
    {
        $date = $this->getCurrentDatetime($object);
        return $date->format($format);
    }


    /** STOPWATCH */

    public function startStopwatch(): static
    {
        $this->stopwatch ??= new Stopwatch(true);
        if(!$this->stopwatch->isStarted(static::STOPWATCH_MAIN_NAME)) {
            $this->stopwatch->start(static::STOPWATCH_MAIN_NAME);
        }
        return $this;
    }

    public function getStopwatch(): ?Stopwatch
    {
        return $this->stopwatch ?? null;
    }

    public function getStopwatchTime(): int|float
    {
        if($this->stopwatch?->isStarted(static::STOPWATCH_MAIN_NAME)) {
            $event = $this->stopwatch->stop(static::STOPWATCH_MAIN_NAME);
            return $event->getDuration();
        }
        return -1;
    }


    /** TINY VALUES */

    public function __call($name, $arguments)
    {
        if(preg_match('/^set/', $name)) {
            $name = lcfirst(preg_replace('/^set/', '', $name));
            return $this->setTinyvalue($name, ...$arguments);
        }
        if(!array_key_exists($name, $this->tinyvalues) && preg_match('/^get/', $name)) {
            $name = lcfirst(preg_replace('/^get/', '', $name));
        }
        if(array_key_exists($name, $this->tinyvalues)) {
            return $this->tinyvalues[$name];
        }
        throw new Exception(vsprintf('Error %s line %d: can not call %s because it does not exist!', [__METHOD__, __LINE__, $name]));
        // return $this->appContext->$name(...$arguments);
    }

    public function setTinyvalue(
        string $name,
        mixed $value,
        bool $controlType = true
    ): static
    {
        if(method_exists($this, lcfirst(preg_replace('/^set/', '', $name)))) {
            throw new Exception(vsprintf('Error %s line %d: name %s for value is not acceptable, please change name!', [__METHOD__, __LINE__, $name]));
        }
        if(method_exists($this, lcfirst(preg_replace('/^get/', '', $name)))) {
            throw new Exception(vsprintf('Error %s line %d: name %s for value is not acceptable, please change name!', [__METHOD__, __LINE__, $name]));
        }
        if($controlType && isset($this->tinyvalues[$name])) {
            $value_type = is_object($value) ? get_class($value) : gettype($value);
            $tiny_type = is_object($this->tinyvalues[$name]) ? get_class($this->tinyvalues[$name]) : gettype($this->tinyvalues[$name]);
            if(!in_array($value_type, ['NULL']) && !in_array($tiny_type, ['NULL'])) {
                if($value_type !== $tiny_type) {
                    throw new Exception(vsprintf('Error %s line %d: value (%s given) is not same type as actual value (got %s)!', [__METHOD__, __LINE__, $value_type, $tiny_type]));
                }
            }
        }
        $this->tinyvalues[$name] = $value;
        return $this;
    }

    public function getTinyvalue(
        string $name,
        mixed $default = null
    ): mixed
    {
        return $this->tinyvalues[$name] ?? $default;
    }

    public function getTinyvalues(): mixed
    {
        return $this->tinyvalues;
    }

    public function setTinyvalues(
        array $values
    ): static
    {
        foreach ($values as $name => $value) {
            $this->setTinyvalue($name, $value, true);
        }
        return $this;
    }

    private function mergeTinyvalues(
        array $values
    ): static
    {
        foreach ($values as $name => $value) {
            $this->tinyvalues[$name] = $value;
        }
        return $this;
    }

    /** SERIALIZE */

    protected function getSerializables(): array
    {
        return [
            // All
            'sessionID' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'user' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'environment' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'firewallname' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'clientIp' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'XmlHttpRequest' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'TurboFrameRequest' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'TurboStreamRequest' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'public' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'private' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'currentdatetime' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'debug' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'locale' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'enabled_locales' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'current_route' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'current_route_parameters' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => false],
            'stopwatchTime' => ['serialize' => true, 'unserialize' => false, 'finalOnly' => true],
            // Unserializable
            'timezone' => ['serialize' => true, 'unserialize' => true, 'finalOnly' => false],
            'datenow' => ['serialize' => true, 'unserialize' => true, 'finalOnly' => false],
            'tinyvalues' => ['serialize' => true, 'unserialize' => 'mergeTinyvalues', 'finalOnly' => false],
        ];
    }

    public function jsonSerialize(
        bool $insertFinals = false
    ): mixed
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
        $data = [];
        foreach ($this->getSerializables() as $property => $values) {
            if($insertFinals || !$values['finalOnly']) {
                $method = 'get'.ucfirst($property);
                if(is_string($values['serialize'])) {
                    $this->{$values['serialize']}();
                } else if(method_exists($this, $method)) {
                    $data[$property] = $this->{$method}();
                } else if($values['serialize']) {
                    $data[$property] = $propertyAccessor->getValue($this, $property);
                }
            }
        }
        return $data;
    }

    public function jsonUnserialize(
        array $data
    ): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
        $sery = $this->getSerializables();
        foreach ($data as $property => $value) {
            $method = 'set'.ucfirst($property);
            if(is_string($sery[$property]['unserialize'])) {
                $this->{$sery[$property]['unserialize']}($value);
            } else if(method_exists($this, $method)) {
                $data[$property] = $this->{$method}($value);
            } else if($sery[$property]['unserialize']) {
                $propertyAccessor->setValue($this, $property, $value);
            }
        }
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
    /** LOCALE / LANGUAGES                                                                                      */
    /************************************************************************************************************/




    /************************************************************************************************************/
    /** SECURITY                                                                                                */
    /************************************************************************************************************/

    // public function getUser(): ?UserInterface
    // {
    //     return $this->security->getUser();
    // }

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


    public function getSessionID(): ?string
    {
        return $this->getSession()?->getId() ?: null;
    }

    public function getClientIp(): ?string
    {
        return $this->getRequest()?->getClientIp() ?: null;
    }


    public function getFirewallConfig(): ?FirewallConfig
    {
        $request = $this->getRequest();
        return $request ? $this->security->getFirewallConfig($request) : null;
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

    public function isCurrentFirewallAvailableForInit(): bool
    {
        $firewalls = $this->getFirewalls();
        $firewalls = array_filter($firewalls, fn($fw) => !in_array($fw, static::EXCLUDED_FIREWALLS));
        return in_array($this->getFirewallName(), $firewalls);
    }


    /************************************************************************************************************/
    /** CACHE                                                                                                   */
    /************************************************************************************************************/

    // public function getCache(): CacheServiceInterface
    // {
    //     return $this->get(CacheServiceInterface::class);
    // }

}