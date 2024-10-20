<?php

namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// Symfony
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\HttpFoundation\Response;

#[AsAlias(WireUserServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class WireUserService extends BaseWireEntityService implements WireUserServiceInterface
{

    protected Security $security;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService
    ) {
        parent::__construct($appWire, $wireEntityService);
        $this->security = $this->appWire->security;
    }

    public function getUser(): ?WireUserInterface
    {
        return $this->security->getUser();
    }

    public function getMainAdminUser(
        bool $findSadminIfNotFound = false
    ): ?WireUserInterface {
        $admin_email = $this->appWire->getParam('main_admin');
        /** @var ServiceEntityRepository */
        $repository = $this->getRepository();
        $user = $repository->findOneByEmail($admin_email);
        return empty($user) && $findSadminIfNotFound
            ? $this->getMainSAdminUser()
            : $user;
    }

    public function getMainSAdminUser(): ?WireUserInterface
    {
        $admin_email = $this->appWire->getParam('main_sadmin');
        /** @var ServiceEntityRepository */
        $repository = $this->getRepository();
        return $repository->findOneByEmail($admin_email);
    }

    public function logoutCurrentUser(bool $validateCsrfToken = true): ?Response
    {
        return $this->security->logout($validateCsrfToken);
    }

    public function updateUserLastLogin(
        WireUserInterface $user
    ): static {
        $user->updateLastLogin();
        $this->wireEntityService->flush();
        return $this;
    }
}
