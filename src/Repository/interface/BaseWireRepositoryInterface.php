<?php
namespace Aequation\WireBundle\Repository\interface;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;

interface BaseWireRepositoryInterface
{
    const ENTITY_CLASS = '';
    const NAME = 'u';

    // Base tools
    public function hasField(string $name): bool;
    public function hasRelation(string $name): bool;
    public static function alias(): string;
    public static function getDefaultAlias(): string;

    // basic querys
    public function count(array $criteria = []): int;
    public function findOneByEuid(string $euid): ?WireEntityInterface;
    public function findEntityByEuidOrUname(string $euidOrUname): ?WireEntityInterface;


}