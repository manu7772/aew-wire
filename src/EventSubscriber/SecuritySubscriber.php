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
use Symfony\Component\Translation\TranslatableMessage;

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
            LogoutEvent::class => 'onLogoutSuccess',
            CheckPassportEvent::class => 'onCheckPassport',
        ];
    }

    public function onSwitchUser(SwitchUserEvent $event)
    {
        if($this->appWire->isDevOrSadmin()) {
            dd('[DEV] USER SWITCHED (in '.__METHOD__.' / line '.__LINE__.'), not supported yet!', $event);
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        /** @var wireUserInterface */
        $user = $event->getUser();
        $this->userService->updateUserLastLogin($user);
        // $this->appWire->integrateUserContext($user);
        $this->appWire->addFlash('success', new TranslatableMessage('Login_successful', [], 'security'));
    }

    // public function onLoginFailure(LoginFailureEvent $event): void
    // {
    //     if ($event->getException() instanceof AccountNotVerifiedAuthenticationException) {
    //         $response = new RedirectResponse(
    //             $this->router->generate('app_home' --> appWire->getPublicHomeRoute())
    //         );
    //         $event->setResponse($response);
    //     }
    // }

    public function onLogoutSuccess(LogoutEvent $event)
    {
        /** @var ?WireUserInterface */
        $user = $event->getToken()->getUser();
        $this->appWire->addFlash('warning', new TranslatableMessage('Logout_successful', [], 'security'));
        if($user) {
            $this->appWire->getFlashBag()->add('appwire', [
                'timezone' => $user->getTimezone(),
                'darkmode' => $user->isDarkmode(),
            ]);
            // $this->appWire->integrateUserContext($user); // --> keep user context in logged out session (darkmode, language, etc.)
            if($this->appWire->isDevOrSadmin()) $this->appWire->addFlash('info', vsprintf('<div>Mode %s - %s</div>', [$this->appWire->getDarkmode() ? 'sombre' : 'clair', $this->appWire->getTimezoneName()]));
        }
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        /** @var ?WireUserInterface */
        $user = $this->appWire->getUser();
        if ($user instanceof WireUserInterface && !$user->isLoggable()) {
            throw new AccountNotVerifiedAuthenticationException();
        }
    }

}