<?php
namespace Aequation\WireBundle\Entity\interface;

// Symfony

use DateInterval;
use DateTimeImmutable;
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
    public function isSadmin(): bool;

    public function getEmail(): ?string;
    public function setEmail(string $email): static;
    public function getUserIdentifier(): string;
    public function getFirstname(): ?string;
    public function setFirstname(string $firstname): static;
    // Roles
    public function getRoles(): array;
    public function getHigherRole(): ?string;
    public function setRoles(array $roles): static;
    public function addRole(string|array $role): static;
    public function removeRole(string|array $roles): static;
    public function HasRole(string $role): bool;
    public function checkRoles(): static;
    // Password / Security
    public function getPlainPassword(): ?string;
    public function setPlainPassword(string $plainPassword): static;
    public function getPassword(): ?string;
    public function setPassword(string $password): static;
    public function setSuperadmin(): static;
    public function isSuperadmin(): bool;
    public function autoGeneratePassword(int $length = 32, ?string $chars = null, bool $replace = true): static;
    // Csstheme
    public function getCsstheme(): string;
    public function setCsstheme(string $csstheme): static;
    // Expires
    public function isExpired(): bool;
    public function expiresIn(): ?DateInterval;
    public function getExpiresAt(): ?\DateTimeImmutable;
    public function setExpiresAt(\DateTimeImmutable $expiresAt): static;
    // Factorys
    public function getIsVerified(): bool;
    public function isVerified(): bool;
    public function setIsVerified(bool $isVerified): static;
    public function updateIsVerified(): static;
    public function getLastLogin(): ?DateTimeImmutable;
    public function setLastLogin(?DateTimeImmutable $lastLogin): static;
    public function updateLastLogin(): static;
    public function eraseCredentials(): void;
    public function getFunctionality(): ?string;
    public function setFunctionality(?string $functionality = null): static;
    public function getDescription(): ?string;
    public function setDescription(?string $description = null): static;
    public function getFactorys(): Collection;
    public function addFactory(WireFactoryInterface $factory): static;
    public function removeFactory(WireFactoryInterface $factory): static;
    public function hasFactory(WireFactoryInterface $factory): bool;


}