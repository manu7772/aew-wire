<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

interface WireUserServiceInterface extends WireItemServiceInterface, RoleHierarchyInterface
{

    public function getUser(): ?WireUserInterface;
    public function getMainAdminUser(): ?WireUserInterface;
    public function getMainSAdminUser(): ?WireUserInterface;
    public function checkMainSuperadmin(): ?WireUserInterface;
    public function logoutCurrentUser(bool $validateCsrfToken = true): ?Response;
    public function updateUserLastLogin(WireUserInterface $user): static;

    public function getUpperRoleNames(string|array|WireUserInterface $roles, bool $filter_main_roles = true): array;

}
