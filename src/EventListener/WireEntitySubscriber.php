<?php
namespace Aequation\WireBundle\EventListener;

use Aequation\WireBundle\Attribute\CurrentUser;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Event\WireEntityEvent;
use Aequation\WireBundle\Service\interface\AttributeWireServiceInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Exception;
// Symfony
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

// Wire Events
#[AsEventListener(event: WireEntityEvent::POST_CREATE, method: 'postCreate')]
#[AsEventListener(event: WireEntityEvent::POST_MODEL, method: 'postModel')]
#[AsEventListener(event: WireEntityEvent::POST_CLONE, method: 'postClone')]
#[AsEventListener(event: WireEntityEvent::BEFORE_PERSIST, method: 'beforePersist')]
#[AsEventListener(event: WireEntityEvent::BEFORE_UPDATE, method: 'beforeUpdate')]
#[AsEventListener(event: WireEntityEvent::BEFORE_REMOVE, method: 'beforeRemove')]
// Doctrine Events
#[AsEventListener(event: Events::prePersist, method: 'prePersist')]
#[AsEventListener(event: Events::preUpdate, method: 'preUpdate')]
#[AsEventListener(event: Events::preRemove, method: 'preRemove')]
// #[AsEventListener(event: Events::onFlush, method: 'onFlush')]
class WireEntitySubscriber
{
    
    public function __construct(
        public readonly AttributeWireServiceInterface $attributeWire
    )
    {}

    public function postCreate(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $this->attributeWire->applyPropertyAttribute(CurrentUser::class, $event);
            $this->attributeWire->applyEventCall(WireEntityEvent::POST_CREATE, $event);
        }
    }

    public function postModel(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $model = $event->getObject();
        }
    }

    public function postClone(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $clone = $event->getObject();
        }
    }

    public function beforePersist(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $entity = $event->getObject();
        }
    }

    public function beforeUpdate(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $entity = $event->getObject();
        }
    }

    public function beforeRemove(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $entity = $event->getObject();
        }
    }

    public function prePersist(PrePersistEventArgs $args) {
        $entity = $args->getObject();
        if($this->attributeWire->appWire->isDev()) {
            dd(vsprintf('TEST %s line %d stopped in DEV environment', [__METHOD__, __LINE__]), $args);
            if($entity instanceof WireEntityInterface && !isset($entity->_estatus)) {
                $this->triggerNotManagedException($entity, __METHOD__, __LINE__);
            }
        }
    }

    public function preUpdate(PreUpdateEventArgs $args) {
        $entity = $args->getObject();
        if($this->attributeWire->appWire->isDev()) {
            dd(vsprintf('TEST %s line %d stopped in DEV environment', [__METHOD__, __LINE__]), $args);
            if($entity instanceof WireEntityInterface && !isset($entity->_estatus)) {
                $this->triggerNotManagedException($entity, __METHOD__, __LINE__);
            }
        }
    }

    public function preRemove(PreRemoveEventArgs $args) {
        $entity = $args->getObject();
        if($this->attributeWire->appWire->isDev()) {
            dd(vsprintf('TEST %s line %d stopped in DEV environment', [__METHOD__, __LINE__]), $args);
            if($entity instanceof WireEntityInterface && !isset($entity->_estatus)) {
                $this->triggerNotManagedException($entity, __METHOD__, __LINE__);
            }
        }
    }

    public static function triggerNotManagedException(
        WireEntityInterface $entity,
        string $method = null,
        int $line = null,
        string $message = null
    ): void
    {
        $message ??= vsprintf('Error %s line %d: entity %s (named: %s) is not managed (no % found in entity\'s property _estatus) while doctrine action!', [$method ?? __METHOD__, $line ?? __LINE__, $entity->getClassname(), $entity->__toString()]);
        throw new Exception($message);
    }

}