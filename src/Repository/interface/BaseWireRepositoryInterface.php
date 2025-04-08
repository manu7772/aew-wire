<?php

namespace Aequation\WireBundle\Repository\interface;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
// Symfony
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\Persistence\ObjectRepository;
// use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

// #[AutoconfigureTag('doctrine.repository_service')]
interface BaseWireRepositoryInterface extends ServiceEntityRepositoryInterface, ObjectRepository
{

    // Base tools
    public function hasField(string $name): bool;
    public function hasRelation(string $name): bool;
    public static function alias(): string;
    public static function getDefaultAlias(): string;

    // basic querys
    public function count(array $criteria = []): int;
    // public function findOneByEuid(string $euid): ?BaseEntityInterface;
    public function findEntityByEuidOrUname(string $euidOrUname): ?BaseEntityInterface;
}
