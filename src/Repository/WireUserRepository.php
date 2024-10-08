<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Repository\BaseWireRepository;
// Symfony
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends BaseWireRepository
 */
abstract class WireUserRepository extends BaseWireRepository implements PasswordUpgraderInterface
{

    const ENTITY_CLASS = WireUser::class;
    const NAME = 'wire_user';

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof WireUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

}
