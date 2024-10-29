<?php
namespace Aequation\WireBundle\Entity\interface;

// Symfony
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface WireUserInterface extends UserInterface, WireEntityInterface, PasswordAuthenticatedUserInterface, TraitEnabledInterface, TraitCreatedInterface, TraitUnamedInterface, TraitScreenableInterface
{

    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public function isEqualTo(UserInterface $user): bool;
    public function isLoggable(): bool;
    public function isVerified(): bool;
    public function updateLastLogin(): static;

    public function getEmail(): ?string;
    public function setEmail(string $email): static;
    public function getUserIdentifier(): string;
    // Roles
    public function getRoles(): array;
    public function setRoles(array $roles): static;
    public function addRole(string $role): static;
    public function HasRole(string $role): bool;
    // Password / Security
    public function getPassword(): ?string;
    public function setPassword(string $password): static;
    public function setSuperadmin(): static;
    public function isValidSuperadmin(): bool;
    public function eraseCredentials(): void;
    // Darkmode
    public function isDarkmode(): bool;
    public function setDarkmode(bool $darkmode): static;


}