<?php
namespace Aequation\WireBundle\EventSubscriber;

use Aequation\WireBundle\Service\AppWireService;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
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
use Symfony\Component\HttpKernel\KernelInterface;

class WireAppGlobalSubscriber implements EventSubscriberInterface
{
    public const DEFAULT_ERROR_TEMPLATE = 'exception/all.html.twig';
    public const TEST_PASSED_NAME = 'test_passed';

    // protected AppWireServiceInterface $appWire;
    // protected RouterInterface $router;

    public function __construct(
        // #[Autowire(service: 'service_container')]
        // protected ContainerInterface $container,
        // protected KernelInterface $kernel,
        protected AppWireServiceInterface $appWire,
        protected RouterInterface $router
    )
    {
        // $this->appWire = $this->container->get(AppWireServiceInterface::class);
        // $this->router = $this->appWire->get('router');
    }

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
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $this->appWire->initialize();
        if(!$event->isMainRequest()) return;
    }

    public function onException(ExceptionEvent $event): void
    {
        // Disable control
        if(!$this->appWire->isProd()) return;
        if($event->getRequest()->query->get('debug', 0) === "1") {
            return;
        }
        // Redirect to Exception Twig page
        /** @var Throwable */
        $exception = $event->getThrowable();
        $statusCode = 500;
        if(method_exists($exception, 'getCode') &&  $exception->getCode() > 0) {
            $statusCode = $exception->getCode();
        } else if(method_exists($exception, 'getStatusCode') &&  $exception->getStatusCode() > 0) {
            $statusCode = $exception->getStatusCode();
        }
        switch (true) {
            case $statusCode >= 100:
                $twigpage_name = $this->getTemplateName($statusCode);
                break;
            // case $exception instanceof HttpExceptionInterface:
            //     $twigpage_name = $this->getTemplateName($statusCode);
            //     break;
            // case $exception instanceof Error:
            //     $twigpage_name = $this->getTemplateName($statusCode);
            //     break;
            // case $exception instanceof LogicException:
            //     $twigpage_name = $this->getTemplateName($statusCode);
            //     break;
            default:
                $twigpage_name = static::DEFAULT_ERROR_TEMPLATE;
                break;
        }
        $context ??= ['exception' => $exception, 'exception_classname' => $exception::class, 'event' => $event, 'twigpage_name' => u($twigpage_name)->afterLast('/'), 'exceptionEvent' => $event];
        $response ??= $this->appWire->getTwig()->render(name: $twigpage_name, context: $context);
        // if($statusCode <= 0) dd($exception, $response);
        $event->setResponse(new Response($response, $statusCode));
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
    //     if(!$event->isMainRequest()) return;
    //     dump($this->appWire->jsonSerialize());
    // }

    public function onFinishRequest(FinishRequestEvent $event): void
    {
        if(!$event->isMainRequest()) return;
        $this->appWire->saveAppWire();
    }

    public function onController(ControllerEvent $event): void
    {
        $this->appWire->initialize();
        if(!$event->isMainRequest()) return;
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
        string $route = null
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

}
