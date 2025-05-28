<?php
namespace Aequation\WireBundle\Entity\interface;

// Symfony

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface WireUserInterface extends WireItemInterface, UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface, TraitRelinkableInterface, TraitWebpageableInterface, TraitCategorizedInterface
{

    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public function isLoggable(): bool;
    public function isVerified(): bool;
    public function updateLastLogin(): static;
    public function isSadmin(): bool;

    public function getEmail(): ?string;
    public function setEmail(string $email): static;
    public function getUserIdentifier(): string;
    // Roles
    public function getRoles(): array;
    public function setRoles(array $roles): static;
    public function addRole(string|array $role): static;
    public function removeRole(string|array $roles): static;
    public function HasRole(string $role): bool;
    public function checkRoles(): static;
    // Password / Security
    public function getPlainPassword(): ?string;
    public function getPassword(): ?string;
    public function setPassword(string $password): static;
    public function setSuperadmin(): static;
    public function isValidSuperadmin(): bool;
    public function eraseCredentials(): void;
    public function autoGeneratePassword(int $length = 32, ?string $chars = null, bool $replace = true): static;
    // Darkmode
    public function isDarkmode(): bool;
    public function setDarkmode(bool $darkmode): static;
    // Factorys
    public function getFactorys(): Collection;
    public function addFactory(WireFactoryInterface $factory): static;
    public function removeFactory(WireFactoryInterface $factory): static;
    public function hasFactory(WireFactoryInterface $factory): bool;


}