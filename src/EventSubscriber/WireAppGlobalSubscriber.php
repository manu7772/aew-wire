<?php
namespace Aequation\WireBundle\EventSubscriber;

use Aequation\WireBundle\Controller\SecurityController;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Security\AccountNotVerifiedAuthenticationException;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Tools\HttpRequest;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use Symfony\Component\DependencyInjection\Attribute\Autowire;
// use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use function Symfony\Component\String\u;
// PHP
use DateTime;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\EventDispatcher\Event;

class WireAppGlobalSubscriber implements EventSubscriberInterface
{
    public const DEFAULT_ERROR_TEMPLATE = 'exception/all.html.twig';
    public const TEST_PASSED_NAME = 'test_passed';
    public const LOGIN_PARAMS_KEYS = ['email','password','_csrf_token'];


    public function __construct(
        // #[Autowire(service: 'service_container')]
        // protected ContainerInterface $container,
        protected AppWireServiceInterface $appWire,
        protected WireUserServiceInterface $userService,
        protected RouterInterface $router
    )
    {}

    /**
     * Get subscribed Events
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            // KernelEvents::EXCEPTION => 'onException',
            KernelEvents::CONTROLLER => 'onController',
            // KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::FINISH_REQUEST => 'onFinishRequest',
            // Login
            // CheckPassportEvent::class => ['onCheckPassport', -10],
            // LoginSuccessEvent::class => 'onLoginSuccess',
            // LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public static function isWdtRequest(
        KernelEvent $event
    ): bool
    {
        return preg_match(AppWireServiceInterface::SECONDARY_PATHS_PATTERN, $event->getRequest()->getPathInfo());
    }

    private function isAvailableActions(
        KernelEvent $event
    ): bool
    {
        return $event->isMainRequest() && !HttpRequest::isCli() && !static::isWdtRequest($event);
    }

    public function onRequest(RequestEvent $event): void
    {
        // if($this->appWire->isTurboFrameRequest()) {
        //     throw new \LogicException('Turbo Frame requests are not supported in this context.'); // TODO: Handle Turbo Frame requests properly
        // }
        // if($this->appWire->isTurboStreamRequest()) {
        //     throw new \LogicException('Turbo Stream requests are not supported in this context.'); // TODO: Handle Turbo Frame requests properly
        // }
        if(!$this->isAvailableActions($event)) return;
        // LOGOUT INVALID USER IMMEDIATLY!!!
        $user = $this->userService->getUser();
        if($user && !$user->isLoggable() && !static::isWdtRequest($event)) {
            if($this->appWire->getCurrent_route() !== SecurityController::ROUTE_LOGGED_OUT) {
                $response = $this->userService->logoutCurrentUser(false);
                $response ??= new RedirectResponse($this->router->generate(SecurityController::ROUTE_LOGGED_OUT));
                $event->setResponse($response);
            }
        }
        if($this->appWire->isRequiredInitialization($event)) {
            $this->appWire->initialize($event);
        }
    }

    public function onException(ExceptionEvent $event): void
    {
        if($this->appWire->isDev()) {
            $normalizer = $this->appWire->get(NormalizerServiceInterface::class);
            dump($normalizer->getCreateds()->toArray());
        }
        return;
        // // Disable control
        // if(!$this->appWire->isProd()) return;
        // if($event->getRequest()->query->get('debug', 0) === "1") {
        //     return;
        // }
        // // Redirect to Exception Twig page
        // /** @var Throwable */
        // $exception = $event->getThrowable();
        // $statusCode = 500;
        // if(method_exists($exception, 'getCode') &&  $exception->getCode() > 0) {
        //     $statusCode = $exception->getCode();
        // } else if(method_exists($exception, 'getStatusCode') &&  $exception->getStatusCode() > 0) {
        //     $statusCode = $exception->getStatusCode();
        // }
        // switch (true) {
        //     case $statusCode >= 100:
        //         $twigpage_name = $this->getTemplateName($statusCode);
        //         break;
        //     // case $exception instanceof HttpExceptionInterface:
        //     //     $twigpage_name = $this->getTemplateName($statusCode);
        //     //     break;
        //     // case $exception instanceof Error:
        //     //     $twigpage_name = $this->getTemplateName($statusCode);
        //     //     break;
        //     // case $exception instanceof LogicException:
        //     //     $twigpage_name = $this->getTemplateName($statusCode);
        //     //     break;
        //     default:
        //         $twigpage_name = static::DEFAULT_ERROR_TEMPLATE;
        //         break;
        // }
        // $context ??= ['exception' => $exception, 'exception_classname' => $exception::class, 'event' => $event, 'twigpage_name' => u($twigpage_name)->afterLast('/'), 'exceptionEvent' => $event];
        // $response ??= $this->appWire->getTwig()->render(name: $twigpage_name, context: $context);
        // // if($statusCode <= 0) dd($exception, $response);
        // $event->setResponse(new Response($response, $statusCode));
    }

    protected function getTemplateName(
        string|int $statusCode
    ): string
    {
        $name = 'exception/'.$statusCode.'.html.twig';
        return $this->appWire->getTwigLoader()->exists($name)
            ? $name
            : static::DEFAULT_ERROR_TEMPLATE;
    }

    // public function onKernelResponse(ResponseEvent $event): void
    // {
    //     if(!$this->appWire->isRequiredInitialization($event)) return;
    //     dump($this->appWire->jsonSerialize());
    // }

    public function onFinishRequest(FinishRequestEvent $event): void
    {
        if(!$this->isAvailableActions($event)) return;
        if($this->appWire->isInitializable($event) && $this->appWire->isInitialized()) {
            $this->appWire->saveAppWire($event);
        }
    }

    public function onController(ControllerEvent $event): void
    {
        if(!$this->isAvailableActions($event)) return;
        if($this->appWire->isRequiredInitialization($event)) {
            $this->appWire->initialize($event);
        }
        // dump($this->appWire->jsonSerialize());
        return;

        // $this->initAppContext($event);
        // $event->getRequest()->getSession()->set(static::TEST_PASSED_NAME, false);
        // dd($this->appWire->getRoute(), $this->appWire->getParameter('lauch_website', null));
        /**
         * @see https://stackoverflow.com/questions/67115605/how-to-redirect-from-a-eventsubscriber-in-symfony-5
         */
        if($this->appWire->getParameter('host_security_enabled', false) && !$this->appWire->isGranted('ROLE_EDITOR')) {
            $controller = $this->getControllerObjectFromEvent($event);
            if($controller instanceof AbstractController) {
                $host = $event->getRequest()->getHost();
                $website_host = preg_replace('/^(www\.)/', '', $this->appWire->getParameter('router.request_context.host', []));

                // **********************************
                // TEST/DEMO WEBSITES RESTRICTED AREA
                // **********************************
                $grantedHosts = [
                    '127.0.0.1',
                    'localhost',
                    $website_host,
                    'www.'.$website_host,
                ];
                if(!in_array($host, $grantedHosts) && $this->isAvailableRouteFor('demotest')) {
                    // Test or Demo Website / Restricted AREA
                    $post_pwd = $event->getRequest()->request->get('demo_password', null);
                    $passwd = $this->appWire->getParameter('host_security_passwd', null);
                    $passed = $event->getRequest()->getSession()->get(static::TEST_PASSED_NAME, false);
                    if(empty($passwd) || $passed) return;
                    if($post_pwd === $passwd) {
                        $event->getRequest()->getSession()->set(static::TEST_PASSED_NAME, true);
                    } else if(!$passed) {
                        $event->setController(function () {
                            $response = $this->appWire->twig->render(name: '@AequationWire/security/test_website.html.twig');
                            return new Response($response, 403);
                        });
                    }
                }

                // **********************************
                // COUNTDOWN/LAUNCH WEBSITES
                // **********************************
                $qualifiedHosts = [
                    // '127.0.0.1',
                    // 'localhost',
                    $website_host,
                    'www.'.$website_host,
                ];
                // $firewall = $this->appWire->getFirewallName();
                // dump($this->appWire->getRoute(), $this->appWire->getParameter('lauch_website', null));
                $context = $this->appWire->getParameter('lauch_website', null);
                if(in_array($host, $qualifiedHosts) && empty($this->appWire->getUser()) && $this->isAvailableRouteFor('countdown') && !empty($context)) {
                    if(new DateTime($context['date']) > new DateTime()) {
                        $context['datetime'] = new DateTime($context['date']);
                        $event->setController(function () use ($context) {
                            $response = $this->appWire->twig->render('@AequationWire/security/countdown.html.twig', $context);
                            return new Response($response, 200);
                        });
                    }
                }
            }
        }
    }

    protected function isAvailableRouteFor(
        string $action,
        ?string $route = null
    ): bool
    {
        $route ??= $this->appWire->route;
        switch ($action) {
            case 'countdown':
                // return !in_array($route, ['app_login','app_logout']) && !$this->appWire->getUser();
                return !in_array($route, ['app_login','app_logout']) && preg_match('/^app_/', $route);
                break;
            case 'demotest':
                return !in_array($route, ['app_login','app_logout']);
                break;
            default:
                return false;
                break;
        }
    }

    protected function getControllerObjectFromEvent(ControllerEvent $event): mixed
    {
        $controller = $event->getController();
        if (true === is_object($controller)) {
            return (object) $controller;
        }
        if (false === is_array($controller)) {
            return null;
        }
        foreach ($controller as $value) {
            if (true === is_object($value)) {
                return $value;
            }
        }
        return null;
    }

    // protected function initAppContext(KernelEvent $event): void
    // {
    //     if(!$this->appWire->hasAppContext()) {
    //         $request = $event->getRequest();
    //         if($session = $request->hasSession() ? $request->getSession() : null) {
    //             $this->appWire->initializeAppContext($session);
    //         }
    //     }
    // }

    // public function onCheckPassport(CheckPassportEvent $event): void
    // {
    //     /** @var ?WireUserInterface */
    //     $user = $this->appWire->getUser();
    //     if ($user instanceof WireUserInterface && !$user->isLoggable()) {
    //         throw new AccountNotVerifiedAuthenticationException();
    //     }
    // }

    // public function onLoginSuccess(LoginSuccessEvent $event): void
    // {
    //     $keys = array_keys($event->getRequest()->request->all());
    //     if(count($keys) >= count(static::LOGIN_PARAMS_KEYS)) {
    //         if(count(array_intersect_key($keys, static::LOGIN_PARAMS_KEYS)) >= count(static::LOGIN_PARAMS_KEYS)) {
    //             /** @var WireUserInterface */
    //             $user = $event->getUser();
    //             // if($this->security->isGranted('ROLE_EDITOR')) {
    //             //     $event->setResponse(new RedirectResponse($this->router->generate('admin_home')));
    //             // }
    //             $this->userService->updateUserLastLogin($user);
    //             $this->appWire->setTinyvalue('darkmode', $user->isDarkmode());
    //         }
    //     }
    //     return;
    // }

    // public function onLoginFailure(LoginFailureEvent $event): void
    // {
    //     if ($event->getException() instanceof AccountNotVerifiedAuthenticationException) {
    //         $response = new RedirectResponse(
    //             $this->router->generate($this->appWire->getRouteHome())
    //         );
    //         $event->setResponse($response);
    //     }
    // }

}
