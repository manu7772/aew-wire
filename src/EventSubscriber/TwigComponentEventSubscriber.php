<?php
namespace Aequation\WireBundle\EventSubscriber;

// Symfony

use Aequation\WireBundle\Twig\interface\DynamicTemplateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

class TwigComponentEventSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [PreRenderEvent::class => 'onPreRender'];
    }


    public function onPreRender(PreRenderEvent $event): void
    {
        // $event->getComponent(); // the component object
        // $event->getTemplate(); // the twig template name that will be rendered
        // $event->getVariables(); // the variables that will be available in the template

        if($event->getComponent() instanceof DynamicTemplateInterface) {
            // Define the template to use dynamically
            $event->setTemplate($event->getComponent()->getTemplate());
        }
    }

}