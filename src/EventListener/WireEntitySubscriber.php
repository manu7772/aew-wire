<?php
namespace Aequation\WireBundle\EventListener;

use Aequation\WireBundle\Attribute\CurrentUser;
use Aequation\WireBundle\Event\WireEntityEvent;
use Aequation\WireBundle\Service\interface\AttributeWireServiceInterface;
// Symfony
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: WireEntityEvent::POST_CREATE, method: 'postCreate')]
#[AsEventListener(event: WireEntityEvent::POST_MODEL, method: 'postModel')]
#[AsEventListener(event: WireEntityEvent::POST_CLONE, method: 'postClone')]
#[AsEventListener(event: WireEntityEvent::BEFORE_PERSIST, method: 'beforePersist')]
#[AsEventListener(event: WireEntityEvent::BEFORE_UPDATE, method: 'beforeUpdate')]
#[AsEventListener(event: WireEntityEvent::BEFORE_REMOVE, method: 'beforeRemove')]
class WireEntitySubscriber
{
    
    public function __construct(
        public readonly AttributeWireServiceInterface $attributeWire
    )
    {
        
    }

    public function postCreate(
        WireEntityEvent $event
    ): void
    {
        // $entity = $event->getEntity();
        $this->attributeWire->applyPropertyAttribute(CurrentUser::class, $event);
        // $this->attributeWire->applyMethodAttribute(CurrentUser::class, $event);
        $this->attributeWire->applyEventCall(WireEntityEvent::POST_CREATE, $event);
    }

    public function postModel(
        WireEntityEvent $event
    ): void
    {
        $model = $event->getEntity();
    }

    public function postClone(
        WireEntityEvent $event
    ): void
    {
        $clone = $event->getEntity();
    }

    public function beforePersist(
        WireEntityEvent $event
    ): void
    {
        $entity = $event->getEntity();
    }

    public function beforeUpdate(
        WireEntityEvent $event
    ): void
    {
        $entity = $event->getEntity();
    }

    public function beforeRemove(
        WireEntityEvent $event
    ): void
    {
        $entity = $event->getEntity();
    }

}