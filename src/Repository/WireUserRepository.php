<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Repository\WireItemRepository;
use Aequation\WireBundle\Repository\interface\WireUserRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends WireItemRepository
 */
abstract class WireUserRepository extends WireItemRepository implements WireUserRepositoryInterface, PasswordUpgraderInterface
{

    const ALIAS = 'wireuser';
    const ENTITY_CLASS = WireUser::class;

    public function __construct(
        ManagerRegistry $registry,
        protected AppWireServiceInterface $appWire,
        protected WireUserServiceInterface $userService
    )
    {
        parent::__construct($registry, $appWire);
    }


    public function findGranted(
        string|array $roles
    ): array
    {
        $qb = $this->createQueryBuilder(static::ALIAS);
        $roles = $this->userService->getUpperRoleNames($roles);
        foreach($roles as $role) {
            $qb->orWhere(static::ALIAS.'.roles LIKE :'.$role)
                ->setParameter($role, "%\"$role\"%");
        }
        return $qb->getQuery()->getResult();
    }

    public function findPaginated(): Query
    {
        $qb = $this->createQueryBuilder(static::ALIAS);
        return $qb->getQuery();
    }

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
