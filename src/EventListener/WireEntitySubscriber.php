<?php
namespace Aequation\WireBundle\EventListener;

use Aequation\WireBundle\Attribute\CurrentUser;
use Aequation\WireBundle\Component\EntityEmbededStatus;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Event\WireEntityEvent;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\AttributeWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Exception;
// Symfony
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

// Wire Events
#[AsEventListener(event: WireEntityEvent::ON_EMBED, method: 'onEmbed')]
#[AsEventListener(event: WireEntityEvent::POST_CREATE, method: 'postCreate')]
#[AsEventListener(event: WireEntityEvent::POST_MODEL, method: 'postModel')]
#[AsEventListener(event: WireEntityEvent::POST_CLONE, method: 'postClone')]
// before go to Doctrine events
#[AsEventListener(event: WireEntityEvent::BEFORE_PERSIST, method: 'beforePersist')]
#[AsEventListener(event: WireEntityEvent::BEFORE_UPDATE, method: 'beforeUpdate')]
#[AsEventListener(event: WireEntityEvent::BEFORE_REMOVE, method: 'beforeRemove')]
// before Form events
#[AsEventListener(event: WireEntityEvent::FORM_PRE_SET_DATA, method: 'formPresetData')]
#[AsEventListener(event: WireEntityEvent::FORM_PRE_SUBMIT, method: 'formPreSubmit')]
// Doctrine Events
// #[AsEventListener(event: Events::postLoad, method: 'postLoad')]
#[AsEventListener(event: Events::prePersist, method: 'prePersist')]
#[AsEventListener(event: Events::preUpdate, method: 'preUpdate')]
#[AsEventListener(event: Events::preRemove, method: 'preRemove')]
// #[AsEventListener(event: Events::onFlush, method: 'onFlush')]
class WireEntitySubscriber
{
    
    public function __construct(
        public readonly AppWireServiceInterface $appWire,
        public readonly WireEntityManagerInterface $wireEntityManager,
        public readonly AttributeWireServiceInterface $attributeWire
    )
    {}

    /**
     * Add Embeded status to entity if not, before any change/action on entity
     * @param WireEntityEvent $event
     * @param string|null $type
     * @return void
     */
    public function beforeAnyChange(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $entity = $event->getObject();
            if(empty($entity->getEmbededStatus())) {
                $entity->setEmbededStatus(new EntityEmbededStatus($entity, $this->appWire));
            }
        }
    }

    public function postCreate(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $entity = $event->getObject();
            // Set Embeded Status
            $entity->setEmbededStatus(new EntityEmbededStatus($entity, $this->appWire));
            // Other actions
            if($entity instanceof TraitUnamedInterface) {
                /** @var UnameInterface $uname */
                $uname = $this->wireEntityManager->createEntity(Uname::class, null);
                // $entity->setUname($uname);
                $uname->attributeEntity($entity);
                if(!empty($uname)) {
                    if(Uname::isValidUname($uname)) {
                        $entity->updateUname($uname);
                    } else if($this->appWire->isDev()) {
                        throw new Exception(vsprintf('Error %s line %d: this uname %s is invalid for new entity %s!', [__METHOD__, __LINE__, json_encode($uname), $entity->getClassname()]));
                    }
                }
            }    
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
            // Set Embeded Status
            $model->setEmbededStatus(new EntityEmbededStatus($model, $this->appWire));
            $model->_estatus->setModel();
        }
    }

    public function postClone(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $clone = $event->getObject();
            // Set Embeded Status
            $clone->setEmbededStatus(new EntityEmbededStatus($clone, $this->appWire));
            $clone->_estatus->setClone();
        }
    }

    public function beforePersist(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $entity = $event->getObject();
            // Has Embeded Status?
            if(empty($entity->getEmbededStatus())) {
                if($this->appWire->isDev()) {
                    throw new Exception(vsprintf('Error %s line %d: entity %s should have embeded status before prePersist!', [__METHOD__, __LINE__, $entity->getClassname()]));
                }
                $entity->setEmbededStatus(new EntityEmbededStatus($entity, $this->appWire));
            }
            $entity->_estatus->addDispatchedEvent(WireEntityEvent::BEFORE_PERSIST);
        }
    }

    public function beforeUpdate(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $entity = $event->getObject();
            $entity->setEmbededStatus(new EntityEmbededStatus($entity, $this->appWire));
            /** @var EntityEmbededStatus */
            $embeded = $entity->_estatus;
            $embeded->applyEvents(WireEntityEvent::BEFORE_UPDATE);
        }
    }

    public function beforeRemove(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $entity = $event->getObject();
            $entity->setEmbededStatus(new EntityEmbededStatus($entity, $this->appWire));
        }
    }

    public function formPresetData(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $entity = $event->getObject();
            $entity->setEmbededStatus(new EntityEmbededStatus($entity, $this->appWire));
        }
    }

    public function formPreSubmit(
        WireEntityEvent $event
    ): void
    {
        if(!$event->isPropagationStopped()) {
            $entity = $event->getObject();
            $entity->setEmbededStatus(new EntityEmbededStatus($entity, $this->appWire));
        }
    }

    public function postLoad(PostLoadEventArgs $args) {
        // $entity = $args->getObject();
        // $entity->setEmbededStatus(new EntityEmbededStatus($entity, EntityEmbededStatus::TYPE_ENTITY_LOADED, $this->appWire));
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
        ?string $method = null,
        ?int $line = null,
        ?string $message = null
    ): void
    {
        $message ??= vsprintf('Error %s line %d: entity %s (named: %s) is not managed (no % found in entity\'s property _estatus) while doctrine action!', [$method ?? __METHOD__, $line ?? __LINE__, $entity->getClassname(), $entity->__toString()]);
        throw new Exception($message);
    }

}