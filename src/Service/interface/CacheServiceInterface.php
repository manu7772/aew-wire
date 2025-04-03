<?php
namespace Aequation\WireBundle\Service\interface;


interface CacheServiceInterface extends WireServiceInterface
{
    public static function isKeyvalid(string $key): bool;
    // main managed caches (with attribute CacheManaged)
    public function getCacheables(bool $reset = false): array;
    // Manage caches
    public function get(
        string $key,
        callable $callback,
        ?string $commentaire = null,
        ?float $beta = null,
        ?array &$metadata = null
    ): mixed;
    public function reset(
        string $key,
        callable $callback,
        ?string $commentaire = null,
        ?float $beta = null,
        ?array &$metadata = null
    ): mixed;
    public function delete(string $key): bool;
    public function deleteAll(): bool;
    public function getKeys(): array;
    public function hasCache(string $key): bool;
}