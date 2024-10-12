<?php
namespace Aequation\WireBundle\Service;

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

// #[AsAlias(WireUserServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WireUserService extends BaseWireEntityService implements WireUserServiceInterface
{

    // public const ENTITY_CLASS = WireUser::class;

    protected Security $security;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService
    )
    {
        parent::__construct($appWire, $wireEntityService);
        $this->security = $this->appWire->security;
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