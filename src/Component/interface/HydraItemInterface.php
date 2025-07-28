<?php
namespace Aequation\WireBundle\Component\interface;


interface HydraItemInterface extends TypedCollectionInterface
{
    public function getName(): string;
    public function getHydradataItems(): HydradataItemsInterface;
    public function getWcmd(): WireClassMetadataInterface|false;
    public function getHydratedEntity(): ?object;
    public function getPersistedOrNew(): ?object;
    public function getPersisted(): ?object;
    public function hasPersisted(): bool;
    public function createDto(array $over_data = []): ?object;
    public function getNew(): ?object;
}