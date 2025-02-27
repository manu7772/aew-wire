<?php
namespace Aequation\WireBundle\EventSubscriber;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Security\AccountNotVerifiedAuthenticationException;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// Symfony
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SecuritySubscriber implements EventSubscriberInterface
{

    public function __construct(
        // #[Autowire(service: 'service_container')]
        // protected ContainerInterface $container,
        protected AppWireServiceInterface $appWire,
        protected WireUserServiceInterface $userService
    )
    {}


    public static function getSubscribedEvents(): array
    {
        return [
            SwitchUserEvent::class => 'onSwitchUser',
            LoginSuccessEvent::class => 'onLoginSuccess',
            // LoginFailureEvent::class => 'onLoginFailure',
            // LogoutEvent::class => 'onLogoutSuccess',
            CheckPassportEvent::class => 'onCheckPassport',
        ];
    }

    public function onSwitchUser(SwitchUserEvent $event)
    {
        if($this->appWire->isDev()) {
            dd('[DEV] USER SWITCHED (in '.__METHOD__.' / line '.__LINE__.'), not supported yet!', $event);
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        /** @var wireUserInterface */
        $user = $event->getUser();
        $this->userService->updateUserLastLogin($user);
        $this->appWire->setTimezone($user->getTimezone());
        $event->getRequest()->getSession()->set('darkmode', $user->isDarkmode());
    }

    // public function onLoginFailure(LoginFailureEvent $event): void
    // {
    //     if ($event->getException() instanceof AccountNotVerifiedAuthenticationException) {
    //         $response = new RedirectResponse(
    //             $this->router->generate('app_home')
    //         );
    //         $event->setResponse($response);
    //     }
    // }

    // public function onLogoutSuccess(LogoutEvent $event)
    // {
    //     // dump($event->getRequest()->getSession()->all());
    //     /** @var User */
    //     $user = $event->getToken()->getUser();
    //     // $this->userService->setDarkmode($user->isDarkmode());
    //     // dump($this->userService->getDarkmode());
    // }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        /** @var ?WireUserInterface */
        $user = $this->appWire->getUser();
        if ($user instanceof WireUserInterface && !$user->isLoggable()) {
            throw new AccountNotVerifiedAuthenticationException();
        }
    }

}