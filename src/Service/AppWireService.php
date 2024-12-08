<?php
namespace Aequation\WireBundle\Service;

// Aequation
use Aequation\WireBundle\Entity\interface\TraitPreferedInterface;
use Aequation\WireBundle\Entity\interface\TraitSlugInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\AttributeWireServiceInterface;
use Aequation\WireBundle\Service\interface\TimezoneInterface;
use Aequation\WireBundle\Tools\HttpRequest;
use Aequation\WireBundle\Tools\Strings;
// Symphony
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\UX\Turbo\TurboBundle;
use Symfony\Bridge\Twig\AppVariable;
use Twig\Loader\LoaderInterface;
use Twig\Environment;
use Twig\Markup;
// PHP
use BadMethodCallException;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use UnitEnum;

/**
 * Class AppWireService
 * @package Aequation\WireBundle\Service
 */
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

    /**
     * AppWireService constructor.
     * 
     * @param KernelInterface $kernel
     * @param Security $security
     * @param Environment $twig
     * @param ParameterBagInterface $parameterBag
     * @param LocaleSwitcher $myLocaleSwitcher
     * @param RequestStack $requestStack
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        public readonly KernelInterface $kernel,
        public readonly Security $security,
        public readonly Environment $twig,
        public readonly ParameterBagInterface $parameterBag,
        public readonly LocaleSwitcher $myLocaleSwitcher, // Override localeSwitcher
        RequestStack $requestStack,
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
        $this->setLocaleSwitcher($myLocaleSwitcher);
        $this->setEnabledLocales(['fr_FR']);
        $this->container = $this->kernel->getContainer();
        // dd($this->container->getParameter('vich_uploader.mappings'), $this->container->getParameter('vich_uploader.metadata'));
        // dd($this->container->getParameter('symfonycasts_tailwind.input_css'));
    }


    /************************************************************************************************************/
    /** HTTP Kernal shortcuts                                                                                   */
    /************************************************************************************************************/

    /**
     * Get charset (from kernel)
     * @return string
     */
    public function getCharset(): string
    {
        return $this->kernel->getCharset();
    }



    /************************************************************************************************************/
    /** IMPLEMENTS AppWireServiceInterface                                                                      */
    /************************************************************************************************************/

    /**
     * Get service as string
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * Get service name
     * @return string
     */
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
     * Get current session
     *
     * @return SessionInterface|null
     */
    public function getSession(): ?SessionInterface
    {
        // if(!isset($this->requestStack)) { ???????????????????????????????????
        //     throw new \RuntimeException(vsprintf('Error %s line %d: session is not available or loaded yet.', [__METHOD__, __LINE__]));
        // }
        $request = $this->getRequest();
        if(!$request) {
            throw new \RuntimeException(vsprintf('Error %s line %d: session is not available or loaded yet.', [__METHOD__, __LINE__]));
        }
        $session = $request?->hasSession() ? $request->getSession() : null;
        return $session;
    }


    /************************************************************************************************************/
    /** SYMFONY / PHP info                                                                                      */
    /************************************************************************************************************/

    /**
     * get Symfony info
     * @return array
     */
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

    /**
     * get PHP info
     * @return array
     */
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

    /**
     * get container
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container ??= $this->kernel->getContainer();
    }

    /**
     * has service (only if public)
     * 
     * @param string $id
     * @return bool
     */
    public function has(
        string $id
    ): bool {
        return $this->getContainer()->has($id);
        // return $this->getContainer()?->has($id) ?: false;
    }

    /**
     * get service (only if public)
     * 
     * @param string $id
     * @param int $invalidBehavior
     * @return object|null
     */
    public function get(
        string $id,
        int $invalidBehavior = ContainerInterface::NULL_ON_INVALID_REFERENCE
    ): ?object {
        return $this->getContainer()->get($id, $invalidBehavior);
    }

    /**
     * get service of object or class
     * service name is described in Attribute of object
     * 
     * @param string|object $objectOrClass
     * @return object|null
     */
    public function getClassService(
        string|object $objectOrClass
    ): ?object {
        $serviceName = $this->get(AttributeWireServiceInterface::class)->getClassServiceName($objectOrClass);
        return !empty($serviceName) && $this->has($serviceName)
            ? $this->get($serviceName)
            : null;
    }


    /************************************************************************************************************/
    /** DIRS                                                                                                    */
    /************************************************************************************************************/

    /**
     * get project dir
     * 
     * @param string|null $path
     * @return string
     */
    public function getProjectDir(
        ?string $path = null
    ): string {
        $path = empty($path) ? '' : preg_replace(['/^\\/*/', '/\\/*$/'], [DIRECTORY_SEPARATOR, ''], $path);
        return $this->kernel->getProjectDir().$path;
    }

    /**
     * get cache dir
     * 
     * @param string|null $path
     * @return string
     */
    public function getCacheDir(
        ?string $path = null
    ): string {
        $path = empty($path) ? '' : preg_replace(['/^\\/*/', '/\\/*$/'], [DIRECTORY_SEPARATOR, ''], $path);
        return $this->kernel->getCacheDir().$path;
    }

    /**
     * get log dir
     * 
     * @param string|null $path
     * @return string
     */
    public function getLogDir(
        ?string $path = null
    ): string {
        $path = empty($path) ? '' : preg_replace(['/^\\/*/', '/\\/*$/'], [DIRECTORY_SEPARATOR, ''], $path);
        return $this->kernel->getLogDir().$path;
    }

    /**
     * get temp dir
     * 
     * @param string|null $path
     * @return string
     */
    public function getTempDir(
        ?string $path = null
    ): string {
        $path = empty($path) ? '' : preg_replace(['/^\\/*/', '/\\/*$/'], [DIRECTORY_SEPARATOR, ''], $path);
        return $this->kernel->getProjectDir().DIRECTORY_SEPARATOR.static::TEMP_DIR.DIRECTORY_SEPARATOR.$path;
    }

    public function getConfigDir(
        ?string $path = null
    ): string {
        $path = empty($path) ? '' : preg_replace(['/^\\/*/', '/\\/*$/'], [DIRECTORY_SEPARATOR, ''], $path);
        return $this->kernel->getProjectDir().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$path;
    }


    /************************************************************************************************************/
    /** PARAMETERS                                                                                              */
    /************************************************************************************************************/

    /**
     * get parameter bag
     * 
     * @return ParameterBagInterface
     */
    public function getParameterBag(): ParameterBagInterface
    {
        return $this->parameterBag;
    }

    /**
     * get parameter
     * 
     * @param string $name
     * @param array|bool|string|int|float|UnitEnum|null $default
     * @return array|bool|string|int|float|UnitEnum|null
     */
    public function getParam(
        string $name,
        array|bool|string|int|float|UnitEnum|null $default = null,
    ): array|bool|string|int|float|UnitEnum|null {
        return $this->getParameter($name, $default);
    }

    /**
     * get parameter
     * 
     * @param string $name
     * @param array|bool|string|int|float|UnitEnum|null $default
     * @return array|bool|string|int|float|UnitEnum|null
     */
    public function getParameter(
        string $name,
        array|bool|string|int|float|UnitEnum|null $default = null,
    ): array|bool|string|int|float|UnitEnum|null {
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

    /**
     * is request XmlHttpRequest
     * 
     * @return bool
     */
    public function isXmlHttpRequest(): bool
    {
        return $this->getRequest()?->isXmlHttpRequest() ?: false;
        // return $this->getRequest()?->headers->get('x-requested-with', null) === 'XMLHttpRequest' ?: false;
    }

    /**
     * is request TurboFrame
     * 
     * @return bool
     */
    public function isTurboFrameRequest(): bool
    {
        return $this->getRequest()?->headers->has('Turbo-Frame') ?: false;
    }

    /**
     * is request TurboStream
     * 
     * @param bool $prepareRequest
     * @return bool
     */
    public function isTurboStreamRequest(
        bool $prepareRequest = false
    ): bool {
        $request = $this->getRequest();
        $isTurbo = $request
            ? $request->getMethod() !== 'GET' && TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()
            : false;
        if($isTurbo && $prepareRequest) $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        return $isTurbo;
    }

    /**
     * get turbo metas
     *
     * @param null|boolean $asMarkup
     * @return string|Markup
     */
    public function getTurboMetas(
        bool $asMarkup = true
    ): string|Markup {
        $default = "preserve";
        $metas = [];
        // Turbo refresh
        $turbo_refresh = $this->getParam('turbo-refresh-scroll', $default);
        if(!empty($turbo_refresh)) {
            $metas[] = '<meta name="turbo-refresh-scroll" content="'.$turbo_refresh.'">';
        }
        $html = implode(PHP_EOL, $metas) ?? '';
        return $asMarkup
            ? Strings::markup(html: $html)
            : $html;
    }

    /**
     * get RequestContext
     * 
     * @return RequestContext
     */
    public function getContext(): RequestContext
    {
        /** @var RouterInterface $router */
        $router = $this->get('router');
        return $router?->getContext() ?: null;
    }

    public function getContextAsArray(): array
    {
        $context = $this->getContext();
        return [
            'BaseUrl' => $context->getBaseUrl(),
            'PathInfo' => $context->getPathInfo(),
            'Method' => $context->getMethod(),
            'Host' => $context->getHost(),
            'Scheme' => $context->getScheme(),
            'HttpPort' => $context->getHttpPort(),
            'HttpsPort' => $context->getHttpsPort(),
            'QueryString' => $context->getQueryString(),
            // 'Parameters' => $context->getParameters(),
        ];
    }

    /**
     * get HeaderBag
     * 
     * @return HeaderBag
     */
    public function getHeaders(): ?HeaderBag
    {
        /** @var Request */
        $request = $this->getRequest();
        return $request?->headers ?: null;
    }

    // public function getHeadersAsArray(): HeaderBag
    // {
    //     return $this->getHeaders();
    //     return $headers?->all();
    //     // return [
    //     //     'User-Agent' => $headers?->get('User-Agent') ?: null,
    //     //     '_keys' => $headers?->keys(),
    //     //     // 'date_User-Agent' => $headers?->getDate('User-Agent', new DateTimeImmutable()),
    //     // ];
    // }


    /************************************************************************************************************/
    /** INITIALIZE                                                                                              */
    /************************************************************************************************************/

    /**
     * initialize service
     * 
     * @return bool
     */
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

    /**
     * retrieve session data for AppWire regarding firewall
     * 
     * @return null|array
     */
    public function retrieveAppWire(
        ?string $firewall = null
    ): ?array {
        $firewall ??= $this->getFirewallName();
        return $this->session->get(static::APP_WIRE_SESSION_PREFIX.$firewall, []);
    }

    /**
     * save session data for AppWire regarding firewall
     * 
     * @return bool
     */
    public function saveAppWire(): bool
    {
        if($this->isInitialized()) {
            $this->session->set(static::APP_WIRE_SESSION_PREFIX.$this->getFirewallName(), $this->jsonSerialize(true));
            if($this->isDev()) dump($this->session->get(static::APP_WIRE_SESSION_PREFIX.$this->getFirewallName()));
            return true;
        }
        return false;
    }

    /**
     * clear session data for AppWire regarding firewall
     * 
     * @return bool
     */
    public function clearAppWire(
        ?string $firewall = null
    ): bool {
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

    /**
     * reset session data for AppWire regarding firewall
     * 
     * @return bool
     */
    public function resetAppWire(): bool
    {
        if($this->clearAppWire()) {
            $this->context_initialized = false;
            return $this->initialize();
        }
        return false;
    }

    /**
     * is service initialized
     * 
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->context_initialized;
    }

    /** TIMEZONE */

    /**
     * get timestamp
     * 
     * @return DateTimeZone
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * get default timezone
     * 
     * @return DateTimeZone
     */
    public function getDefaultTimezone(): DateTimeZone
    {
        $timezone = $this->container->hasParameter('timezone') ? $this->container->getParameter('timezone') : static::DEFAULT_TIMEZONE;
        return new DateTimeZone($timezone);
    }

    /**
     * get timezone
     * 
     * @return DateTimeZone
     */
    public function getTimezone(): DateTimeZone
    {
        return $this->timezone ?? $this->getDefaultTimezone();
    }

    /**
     * get timezone name (as string)
     * 
     * @return string
     */
    public function getTimezoneName(): string
    {
        return $this->getTimezone()->getName();
    }

    /**
     * set timezone
     * 
     * @param string|DateTimeZone $timezone
     * @return static
     */
    public function setTimezone(
        string|DateTimeZone $timezone
    ): static {
        $this->timezone = is_string($timezone) ? new DateTimeZone($timezone) : $timezone;
        return $this;
    }

    /** DATENOW */

    /**
     * get default datenow
     * 
     * @return string
     */
    public function getDefaultDatenow(): string
    {
        return $this->container->hasParameter('datenow') ? $this->container->getParameter('datenow') : static::DEFAULT_DATENOW;
    }

    /**
     * get datenow
     * 
     * @return string
     */
    public function getDatenow(): string
    {
        return $this->datenow ?? $this->getDefaultDatenow();
    }

    /**
     * set datenow
     * 
     * @param string $datenow
     * @return static
     */
    public function setDatenow(
        string $datenow
    ): static {
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
     *
     */
    public function getDatetimeTZ(
        string|DateTimeImmutable $date = 'now'
    ): DateTimeImmutable {
        return $date instanceof DateTimeImmutable
            ? $date->setTimezone($this->getTimezone())
            : new DateTimeImmutable($date, $this->getTimezone());
    }

    /**
     * Get a new DateTime object with the current DateTime
     * and current or the given TimeZone
     * 
     * @param null|TimezoneInterface $object
     * @return DateTimeImmutable
     */
    public function getCurrentDatetime(
        ?TimezoneInterface $object = null
    ): DateTimeImmutable {
        $timezone = $object ? $object->getDateTimezone() : $this->getTimezone();
        return new DateTimeImmutable($this->getDatenow(), $timezone);
    }

    /**
     * Get a formated DateTime string with the current DateTime
     * and current or the given TimeZone
     * 
     * @param string $format
     * @param null|TimezoneInterface $object
     * @return string
     */
    public function getCurrentDatetimeFormated(
        string $format = DATE_ATOM,
        ?TimezoneInterface $object = null
    ): string {
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
        throw new Exception(vsprintf('Error %s line %d: can not call "%s" because it does not exist!', [__METHOD__, __LINE__, $name]));
        // return $this->appContext->$name(...$arguments);
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->tinyvalues);
    }

    public function __get($name)
    {
        return $this->tinyvalues[$name];
    }

    public function __set($name, $value)
    {
        $this->tinyvalues[$name] = $value;
        return $this;
    }

    public function setTinyvalue(
        string $name,
        mixed $value,
        bool $controlType = true
    ): static {
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
    ): mixed {
        return $this->tinyvalues[$name] ?? $default;
    }

    public function getTinyvalues(): mixed
    {
        return $this->tinyvalues;
    }

    public function setTinyvalues(
        array $values
    ): static {
        foreach ($values as $name => $value) {
            $this->setTinyvalue($name, $value, true);
        }
        return $this;
    }

    private function mergeTinyvalues(
        array $values
    ): static {
        foreach ($values as $name => $value) {
            $this->tinyvalues[$name] = $value;
        }
        return $this;
    }

    /** SERIALIZE */

    protected function getSerializableProperties(): array
    {
        return [
            // All
            'sessionID' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'user' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'environment' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'firewallname' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'clientIp' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'clientIps' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'XmlHttpRequest' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'TurboFrameRequest' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'TurboStreamRequest' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'public' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'private' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'currentdatetime' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'debug' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'locale' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'enabled_locales' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'current_route' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'current_route_parameters' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            'stopwatch' => ['serialize' => true, 'unserialize' => false, 'serializable' => 'getStopwatchTime'],
            'Context' => ['serialize' => true, 'unserialize' => false, 'serializable' => 'getContextAsArray'],
            'Headers' => ['serialize' => true, 'unserialize' => false, 'serializable' => true],
            // To unserialize
            'timezone' => ['serialize' => true, 'unserialize' => true, 'serializable' => true],
            'datenow' => ['serialize' => true, 'unserialize' => true, 'serializable' => true],
            'tinyvalues' => ['serialize' => true, 'unserialize' => 'mergeTinyvalues', 'serializable' => true],
        ];
    }

    public function jsonSerialize(
        bool $onlySerializable = false
    ): mixed {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
        $data = [];
        foreach ($this->getSerializableProperties() as $property => $values) {
            if(!$onlySerializable || $values['serializable']) {
                $method = 'get'.ucfirst($property);
                if($onlySerializable && is_string($values['serializable'])) {
                    $data[$property] = $this->{$values['serializable']}();
                } else if(is_string($values['serialize'])) {
                    $data[$property] = $this->{$values['serialize']}();
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
    ): void {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
        $sery = $this->getSerializableProperties();
        foreach ($data as $property => $value) {
            if(isset($sery[$property]) && isset($sery[$property]['unserialize'])) {
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

    /**
     * get twig
     * 
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }

    /**
     * get twig loader
     * 
     * @return LoaderInterface
     */
    public function getTwigLoader(): LoaderInterface
    {
        return $this->twig->getLoader();
    }


    /************************************************************************************************************/
    /** LOCALE / LANGUAGES                                                                                      */
    /************************************************************************************************************/

    /**
     * Switch to a new locale, execute a callback, then switch back to the original.
     * @template T
     * @param callable(string $locale):T $callback
     * @return T
     */
    public function runWithLocale(
        string $locale, callable $callback
    ): static
    {
        $this->myLocaleSwitcher->runWithLocale($locale, $callback);
        return $this;
    }

    /**
     * Reset locale to the original one.
     *
     * @return static
     */
    public function resetLocale(): static
    {
        $this->myLocaleSwitcher->reset();
        return $this;
    }


    /************************************************************************************************************/
    /** SECURITY                                                                                                */
    /************************************************************************************************************/

    /**
     * is subject granted
     * 
     * @return UserInterface|null
     */
    public function isGranted(
        mixed $attributes,
        mixed $subject = null
    ): bool {
        return $this->security->isGranted($attributes, $subject);
    }

    /**
     * is public firewall
     * 
     * @return bool
     */
    public function isPublic(): bool
    {
        return in_array(strtolower($this->getFirewallName()), static::PUBLIC_FIREWALLS);
    }

    /**
     * is private firewall
     * 
     * @return bool
     */
    public function isPrivate(): bool
    {
        return !$this->isPublic();
    }

    /**
     * is CLI
     * 
     * @return bool
     */
    public static function isCli(): bool
    {
        return HttpRequest::isCli();
    }

    /**
     * is dev environment
     * 
     * @return bool
     */
    public function isDev(): bool
    {
        return $this->kernel->getEnvironment() === 'dev';
    }

    /**
     * is prod environment
     * 
     * @return bool
     */
    public function isProd(): bool
    {
        return $this->kernel->getEnvironment() === 'prod';
    }

    /**
     * is test environment
     * 
     * @return bool
     */
    public function isTest(): bool
    {
        return $this->kernel->getEnvironment() === 'test';
    }

    /**
     * get session ID
     * 
     * @return null|string
     */
    public function getSessionID(): ?string
    {
        return $this->getSession()?->getId() ?: null;
    }

    /**
     * get client IP
     *
     * @return string|null
     */
    public function getClientIp(): ?string
    {
        return $this->getRequest()?->getClientIp() ?: null;
    }

    /**
     * get client IPs
     *
     * @return array
     */
    public function getClientIps(): array
    {
        return $this->getRequest()?->getClientIps() ?: [];
    }

    /**
     * get firewall config
     * 
     * @return FirewallConfig|null
     */
    public function getFirewallConfig(): ?FirewallConfig
    {
        $request = $this->getRequest();
        return $request ? $this->security->getFirewallConfig($request) : null;
    }

    /**
     * get firewall name
     * 
     * @return string|null
     */
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

    /**
     * get firewall names
     * 
     * @return array
     */
    public function getFirewalls(): array
    {
        return $this->container->getParameter('security.firewalls');
    }

    /**
     * get main firewalls
     * 
     * @return array
     */
    public function getMainFirewalls(): array
    {
        $firewalls = $this->getFirewalls();
        return array_filter($firewalls, fn($fw) => !in_array($fw, static::EXCLUDED_FIREWALLS));
    }

    /**
     * get firewall choices
     * 
     * @param bool $onlyMains
     * @return array
     */
    public function getFirewallChoices(
        bool $onlyMains = true,
    ): array {
        $firewalls = $onlyMains
            ? $this->getMainFirewalls()
            : $this->getFirewalls();
        return array_combine($firewalls, $firewalls);
    }

    /**
     * is current firewall available for initialization
     * 
     * @return bool
     */
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

    /************************************************************************************************************/
    /** ROUTES                                                                                                  */
    /************************************************************************************************************/

    /**
     * get RouteCollection
     * 
     * @return RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->get('router')->getRouteCollection();
    }

    /**
     * route exists
     * 
     * @return bool
     */
    public function routeExists(string $route, bool|array $control_generation = false): bool
    {
        $exists = $this->getRoutes()->get($route) !== null;
        if($exists && $control_generation) {
            try {
                $this->get('router')->generate($route, is_array($control_generation) ? $control_generation : []);
            } catch (\Throwable $th) {
                //throw $th;
                $exists = false;
            }
        }
        return $exists;
    }

    /**
     * is current route
     * 
     * @param string $route
     * @param mixed $param
     * @return bool
     */
    public function isCurrentRoute(
        string $route,
        mixed $param = null
    ): bool {
        // dump($this->getCurrent_route(), $this->getCurrent_route_parameters(), $param instanceof MenuInterface ? $param->getItems() : null);
        if($param instanceof WireWebpageInterface && $param instanceof TraitPreferedInterface) {
            if($param->isPrefered() && $this->getCurrent_route() === 'app_home') return true;
        }
        if($route !== $this->getCurrent_route()) return false;
        if(!empty($param)) {
            if($param instanceof TraitSlugInterface) {
                if($param instanceof WireWebpageInterface) {
                    if($param->isPrefered() && empty($this->getCurrent_route_parameters())) return true;
                }
                if($param instanceof WireMenuInterface) {
                    foreach ($param->getItems() as $item) {
                        if(in_array($item->getSlug(), $this->getCurrent_route_parameters())) return true;
                    }
                }
                $param = $param->getSlug();
            }
            return in_array($param, $this->getCurrent_route_parameters());
        }
        return true;
    }

    /**
     * get current route as Route object
     * 
     * @return Route|null
     */
    public function getCurrent_route_object(): ?Route
    {
        $route = $this->getCurrent_route();
        return $route
            ? $this->getRoutes()->get($route)
            : null;
    }

    /**
     * Get URL of route only if can be generated
     * public const ABSOLUTE_URL = 0;
     * public const ABSOLUTE_PATH = 1;
     * public const RELATIVE_PATH = 2;
     * public const NETWORK_PATH = 3;
     *
     * @param string $route
     * @param array $parameters
     * @param [type] $referenceType
     * @return string|null
     */
    public function getUrlIfExists(
        string $route,
        array $parameters = [],
        ?int $referenceType = null,
        null|array|string $methods = null
    ): ?string {
        $objroute = $this->getRoutes()->get($route);
        if(!($objroute instanceof Route)) return null;
        // Methods
        $route_methods = $objroute->getMethods();
        if(!empty($methods) && !empty($route_methods)) {
            $valids = array_intersect((array)$methods, $$route_methods);
            if(empty($valids)) return null;
        }
        $current_route = $this->getCurrent_route();
        // if(!$this->getRoutes()->get($route)) return null;

        // ? : avoid if is same as current route / includes logic security
        if(preg_match('/^\?+/', $route)) {
            $user = $this->getUser();
            $route = preg_replace('/^\?+/', '', $route);
            switch (true) {
                case preg_match('/login/', $route):
                    if(preg_match('/login/', $current_route) || $user) return null;
                    break;
                case preg_match('/logout/', $route):
                    if(preg_match('/logout/', $current_route) || !$user) return null;
                    break;
                default:
                    if($route === $current_route) return null;
                    break;
            }
        }
        try {
            /** @var RouterInterface */
            $router = $this->get('router');
            $url = $router->generate(name: $route, parameters: $parameters, referenceType: $referenceType);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $url ?? null;
    }
}
