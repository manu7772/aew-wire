<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Repository\BaseWireRepository;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// Symfony
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

#[AsAlias(WireUserServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class WireUserService extends BaseService implements WireUserServiceInterface
{
    public const ENTITY_CLASS = WireUser::class;

    protected Security $security;
    protected BaseWireRepositoryInterface $repository;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService
    )
    {
        $this->security = $this->appWire->security;
    }

    public function getRepository(): BaseWireRepositoryInterface
    {
        return $this->repository ??= $this->wireEntityService->getRepository(static::ENTITY_CLASS);
    }

    public function getUser(): ?UserInterface
    {
        return $this->security->getUser();
    }

    public function getMainAdminUser(
        bool $findSadminIfNotFound = false
    ): ?UserInterface
    {
        $admin_email = $this->appWire->getParam('main_admin');
        /** @var ServiceEntityRepository */
        $repository = $this->getRepository();
        $user = $repository->findOneByEmail($admin_email);
        return empty($user) && $findSadminIfNotFound
            ? $this->getMainSAdminUser()
            : $user;
    }

    public function getMainSAdminUser(): ?UserInterface
    {
        $admin_email = $this->appWire->getParam('main_sadmin');
        /** @var ServiceEntityRepository */
        $repository = $this->getRepository();
        return $repository->findOneByEmail($admin_email);
    }

}