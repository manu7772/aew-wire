<?php

namespace Aequation\WireBundle\EventListener;

// Aequation
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// PHP
use Exception;

#[AsDoctrineListener(event: Events::postLoad, priority: GlobalDoctrineListener::PRIORITY)]
#[AsDoctrineListener(event: Events::prePersist, priority: GlobalDoctrineListener::PRIORITY)]
#[AsDoctrineListener(event: Events::postPersist, priority: GlobalDoctrineListener::PRIORITY)]
#[AsDoctrineListener(event: Events::preUpdate, priority: GlobalDoctrineListener::PRIORITY)]
#[AsDoctrineListener(event: Events::postUpdate, priority: GlobalDoctrineListener::PRIORITY)]
#[AsDoctrineListener(event: Events::preRemove, priority: GlobalDoctrineListener::PRIORITY)]
#[AsDoctrineListener(event: Events::postRemove, priority: GlobalDoctrineListener::PRIORITY)]
#[AsDoctrineListener(event: Events::postFlush, priority: GlobalDoctrineListener::PRIORITY)]
#[AsDoctrineListener(event: Events::onClear, priority: GlobalDoctrineListener::PRIORITY)]
class GlobalDoctrineListener
{
    public const PRIORITY = 100;

    public function __construct(
        private WireEntityManagerInterface $wireEm,
        private UserPasswordHasherInterface $userPasswordHasher,
        // private AppWireServiceInterface $appWire,
    ) {}

    public function postLoad(
        PostLoadEventArgs $event
    ): void
    {
        $entity = $event->getObject();
        if (!($entity instanceof BaseEntityInterface)) return;
        $this->wireEm->postLoaded($entity);
    }

    public function prePersist(
        PrePersistEventArgs $event
    ): void
    {
        $entity = $event->getObject();
        if (!($entity instanceof BaseEntityInterface)) return;
        $this->checkIntegrity($entity, __METHOD__, __LINE__);
        // User
        if($entity instanceof WireUserInterface) {
            $plainPassword = $entity->getPlainPassword();
            $entity->setPassword($this->userPasswordHasher->hashPassword($entity, $plainPassword));
        }
    }

    public function postPersist(
        PostPersistEventArgs $event
    ): void {
        /** @var BaseEntityInterface */
        $entity = $event->getObject();
        if (!($entity instanceof BaseEntityInterface)) return;
        $entity->__selfstate->setPersisted();
    }

    public function preUpdate(
        PreUpdateEventArgs $event
    ): void
    {
        $entity = $event->getObject();
        if (!($entity instanceof BaseEntityInterface)) return;
        $this->checkIntegrity($entity, __METHOD__, __LINE__);
        if($entity instanceof WireUserInterface) {
            $plainPassword = $entity->getPlainPassword();
            if (!empty($plainPassword)) {
                $entity->setPassword($this->userPasswordHasher->hashPassword($entity, $plainPassword));
            }
        }
    }

    public function postUpdate(
        PostUpdateEventArgs $event
    ): void {
        $entity = $event->getObject();
        if (!($entity instanceof BaseEntityInterface)) return;
        $entity->__selfstate->setUpdated();
    }

    public function preRemove(
        PreRemoveEventArgs $event
    ): void
    {
        $entity = $event->getObject();
        if (!($entity instanceof BaseEntityInterface)) return;
        switch (true) {
            case $entity instanceof WireUserInterface:
                if (array_intersect($entity->getRoles(), ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN'])) {
                    throw new Exception('You cannot delete the admin or super admin, please downgrade him before.');
                }
                break;
        }
    }

    public function postRemove(
        PostRemoveEventArgs $event
    ): void {
        $entity = $event->getObject();
        if (!($entity instanceof BaseEntityInterface)) return;
        $entity->__selfstate->setRemoved();
    }

    public function postFlush(
        PostFlushEventArgs $event
    ): void {
        // Remove all persisteds
        // $this->wireEm->clearPersisteds();
    }

    public function onClear(
        OnClearEventArgs $event
    ): void {
        $this->wireEm->clearCreateds();
    }


    private function checkIntegrity(
        BaseEntityInterface $entity,
        string $method = __METHOD__,
        int $line = __LINE__
    ): void
    {
        if($entity->__selfstate->isModel()) {
            // dump($entity);
            throw new Exception(vsprintf('Error %s line %d: %s %s is a model!', [$method, $line, $entity->getClassname(), $entity]));
        }
    }

}
