<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Symfony\Component\HttpFoundation\Response;

interface WireUserServiceInterface extends WireEntityServiceInterface
{

    public function getUser(): ?WireUserInterface;
    public function getMainAdminUser(): ?WireUserInterface;
    public function getMainSAdminUser(): ?WireUserInterface;
    public function logoutCurrentUser(bool $validateCsrfToken = true): ?Response;
    public function updateUserLastLogin(WireUserInterface $user): static;

}
