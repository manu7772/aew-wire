<?php
namespace Aequation\WireBundle\EventListener;

// Aequation
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// PHP
use Exception;

#[AsDoctrineListener(event: Events::postLoad, priority: 100)]
#[AsDoctrineListener(event: Events::prePersist, priority: 100)]
#[AsDoctrineListener(event: Events::preUpdate, priority: 100)]
#[AsDoctrineListener(event: Events::postFlush)]
class GlobalDoctrineListener
{

    public function __construct(
        private WireEntityManagerInterface $wire_em,
        private UserPasswordHasherInterface $userPasswordHasher,
        // private AppWireServiceInterface $appWire,
    ) {
    }

    public function postLoad(PostLoadEventArgs $event): void
    {
        /** @var WireEntityInterface */
        $entity = $event->getObject();
        $this->wire_em->checkIntegrity($entity, $event);
        switch (true) {
            case $entity instanceof WireUserInterface:
                # code...
                break;
        }
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        /** @var WireEntityInterface */
        $entity = $event->getObject();
        $this->wire_em->checkIntegrity($entity, $event);
        switch (true) {
            case $entity instanceof WireUserInterface:
                $plainPassword = $entity->getPlainPassword();
                $entity->setPassword($this->userPasswordHasher->hashPassword($entity, $plainPassword));
                break;
        }
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        /** @var WireEntityInterface */
        $entity = $event->getObject();
        $this->wire_em->checkIntegrity($entity, $event);
        switch (true) {
            case $entity instanceof WireUserInterface:
                $plainPassword = $entity->getPlainPassword();
                if(!empty((string)$plainPassword)) {
                    $entity->setPassword($this->userPasswordHasher->hashPassword($entity, $plainPassword));
                }
                break;
        }
    }
    
    public function preRemove(PreRemoveEventArgs $event): void
    {
        /** @var WireEntityInterface */
        $entity = $event->getObject();
        switch (true) {
            case $entity instanceof WireUserInterface:
                if(array_intersect($entity->getRoles(), ['ROLE_SUPER_ADMIN','ROLE_ADMIN'])) {
                    throw new Exception('You cannot delete the admin or super admin, please downgrade him before.');
                }
                break;
        }
    }

    public function postFlush(): void
    {
        // Remove all persisteds
        $this->wire_em->clearPersisteds();
    }


} 