<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;

interface WireEntityServiceInterface extends WireServiceInterface
{

    // New
    public function createEntity(string $uname = null): WireEntityInterface;
    public function createModel(): WireEntityInterface;
    public function createClone(WireEntityInterface $entity, string $uname = null, int $clone_method = 1): ?WireEntityInterface;
    // Querys
    public function getEntityClassname(): ?string;
    public function getRepository(): BaseWireRepositoryInterface;
    public function getEntitiesCount(array $criteria = []): int;
    // Persit
    public function persist(WireEntityInterface $entity, bool $flush = false): static;
    public function update(WireEntityInterface $entity, bool $flush = false): static;
    public function remove(WireEntityInterface $entity, bool $flush = false): static;
    public function flush(): static;

}