<?php
namespace Aequation\WireBundle\Entity\interface;

// Symfony
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface WireUserInterface extends UserInterface, WireEntityInterface, PasswordAuthenticatedUserInterface
{

    public function isEqualTo(UserInterface $user): bool;
    public function canLogin(): bool;

    public function getEmail(): ?string;
    public function setEmail(string $email): static;
    public function getUserIdentifier(): string;
    public function getRoles(): array;
    public function setRoles(array $roles): static;
    public function getPassword(): ?string;
    public function setPassword(string $password): static;
    public function eraseCredentials(): void;


}