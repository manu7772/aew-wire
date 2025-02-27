<?php
namespace Aequation\WireBundle\EventListener;

// Aequation
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postLoad, priority: 100)]
#[AsDoctrineListener(event: Events::prePersist, priority: 100)]
#[AsDoctrineListener(event: Events::preUpdate, priority: 100)]
#[AsDoctrineListener(event: Events::postFlush)]
class GlobalDoctrineListener
{

    public function __construct(
        private AppWireServiceInterface $appWire,
        private WireEntityManagerInterface $wire_em
    ) {
    }

    public function postLoad(PostLoadEventArgs $event): void
    {
        /** @var WireEntityInterface */
        $entity = $event->getObject();
        if($this->appWire->isDev()) {
            $this->wire_em->checkIntegrity($entity, $event);
        }
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        /** @var WireEntityInterface */
        $entity = $event->getObject();
        if($this->appWire->isDev()) {
            $this->wire_em->checkIntegrity($entity, $event);
        }

    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        /** @var WireEntityInterface */
        $entity = $event->getObject();
        if($this->appWire->isDev()) {
            $this->wire_em->checkIntegrity($entity, $event);
        }
    }

    public function postFlush(): void
    {
        // Remove all persisteds
        $this->wire_em->clearPersisteds();
    }


} 