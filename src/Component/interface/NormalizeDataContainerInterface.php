<?php
namespace Aequation\WireBundle\Component\interface;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;

interface NormalizeDataContainerInterface
{
    public function isProd(): bool;
    public function isDev(): bool;
    public function getType(): string;
    // public function setEntity(WireEntityInterface $entity): static;
    public function finalizeEntity(WireEntityInterface $entity): bool;
    public function getEntity(): ?WireEntityInterface;
    public function hasEntity(): bool;
    public function getContext(): array;
    public function setContext(array $context): static;
    public function addContext(string $key, mixed $value): static;
    public function removeContext(string $key): static;
    public function mergeContext(array $context, bool $replace = true): static;
    public function getNormalizationContext(): array;
    public function getDenormalizationContext(): array;
    public function setMainGroup(string $main_group): static;
    public function resetMainGroup(): static;
    public function getMainGroup(): string;
    public function isCreateOnly(): bool;
    public function isCreateOrFind(): bool;
    public function isModel(): bool;
    public function isEntity(): bool;
    public function getOptions(): array;
    public function getData(): array;
    public function setData(array $data): static;

}