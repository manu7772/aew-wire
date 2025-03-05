<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface WireUserServiceInterface extends WireItemServiceInterface, RoleHierarchyInterface
{

    public function getSecurity(): Security;
    public function getUser(): ?WireUserInterface;
    public function getMainAdminUser(): ?WireUserInterface;
    public function getMainSAdminUser(): ?WireUserInterface;
    public function checkMainSuperadmin(): ?WireUserInterface;
    public function loginUser(WireUserInterface|string $user): ?Response;
    public function logoutCurrentUser(bool $validateCsrfToken = true): ?Response;
    public function updateUserLastLogin(WireUserInterface $user): static;
    public function isGranted($attribute,  $subject = null): bool;
    public function isUserGranted(?UserInterface $user, $attributes, $object = null, ?string $firewallName = 'main'): bool;
    public function isRolesGranted(string|array $roles, $attributes, $object = null, ?string $firewallName = 'main'): bool;
    public function getRolesMap(): array;
    public function getAppRoles(bool $filter_main_roles = true): array;
    public static function filterChoiceRoles(array|WireUserInterface $roles): array;
    public function getAvailableRoles(string|array|WireUserInterface $roles, bool $filter_main_roles = true): array;
    public function getUpperRoleNames(string|array|WireUserInterface $roles, bool $filter_main_roles = true): array;
    public function compareUsers(WireUserInterface $inferior, WireUserInterface $manager): bool;
    public function saveUser(WireUserInterface $user): static;
    public function createDefaultSuperAdmin(): WireUserInterface;
    public function getSuperadmins(): array;
    public function getAdmins(): array;

}