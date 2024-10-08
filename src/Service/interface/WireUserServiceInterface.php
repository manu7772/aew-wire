<?php
namespace Aequation\WireBundle\Service\interface;

// Symfony
use Symfony\Component\Security\Core\User\UserInterface;

interface WireUserServiceInterface extends WireEntityServiceInterface
{

    public function getUser(): ?UserInterface;
    public function getMainAdminUser(): ?UserInterface;
    public function getMainSAdminUser(): ?UserInterface;

}
