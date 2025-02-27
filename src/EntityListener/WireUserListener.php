<?php
namespace Aequation\WireBundle\EntityListener;

// Aequation
use Aequation\WireBundle\Entity\interface\WireUserInterface;
// Symfony
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsEntityListener(event: Events::prePersist, entity: WireUserInterface::class)]
#[AsEntityListener(event: Events::preUpdate, entity: WireUserInterface::class)]
#[AsEntityListener(event: Events::preRemove, entity: WireUserInterface::class)]
class WireUserListener
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher,
        private ParameterBagInterface $parameterBag,
    ) {}

    public function prePersist(WireUserInterface $user, LifecycleEventArgs $event): void
    {
        $plainPassword = $user->getPlainPassword();
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $plainPassword));
    }

    public function preUpdate(WireUserInterface $user, LifecycleEventArgs $event): void
    {
        $plainPassword = $user->getPlainPassword();
        if(!empty((string)$plainPassword)) {
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $plainPassword));
        }
    }

    public function preRemove(WireUserInterface $user, LifecycleEventArgs $event): void
    {
        if(array_intersect($user->getRoles(), ['ROLE_SUPER_ADMIN','ROLE_ADMIN'])) {
            throw new \Exception('You cannot delete the admin or super admin, please downgrade him before.');
        }
    }

}