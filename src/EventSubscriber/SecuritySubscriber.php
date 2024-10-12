<?php
namespace Aequation\WireBundle\EventSubscriber;

use Aequation\WireBundle\Entity\interface\TraitCreatedInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// Symfonuy
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SecuritySubscriber implements EventSubscriberInterface
{

    public function __construct(
        // #[Autowire(service: 'service_container')]
        // protected ContainerInterface $container,
        protected AppWireServiceInterface $appWire
    )
    {}


    public static function getSubscribedEvents(): array
    {
        return [
            SwitchUserEvent::class => 'onSwitchUser',
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onSwitchUser(SwitchUserEvent $event)
    {
        // $appWire = $this->container->get(AppWireServiceInterface::class);
        if($this->appWire->isDev()) {
            dd('[DEV] USER SWITCHED (in '.__METHOD__.' / line '.__LINE__.')!!!', $event);
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        /** @var UserInterface|TraitCreatedInterface */
        $user = $event->getUser();
        $this->appWire->setTimezone($user->getTimezone());
        // $this->container->get(AppWireServiceInterface::class)->updateContextUser($event);
    }

}