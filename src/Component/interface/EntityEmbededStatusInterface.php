<?php
namespace Aequation\WireBundle\Component\interface;


interface EntityEmbededStatusInterface
{
    public const STATUS_REPEATABLE = false;
    public const TYPE_UNDEFINED = 'undefined';
    public const TYPE_ENTITY = 'entity';
    public const TYPE_ENTITY_CREATED = 'created';
    public const TYPE_ENTITY_LOADED = 'loaded';
    public const TYPE_MODEL = 'model';
    public const TYPE_CLONE = 'clone';
    // Entity
    public const ENTITY_STATUS_NULL = 0;
    public const ENTITY_STATUS_CREATED = 1;
    public const ENTITY_STATUS_LOADED = 2;
    public const ENTITY_STATUS_PERSISTED = 4;
    public const ENTITY_STATUS_FLUSHING = 8;
    public const ENTITY_STATUS_FLUSHED = 16;
    public const ENTITY_STATUS_TODELETE = 32;
    public const ENTITY_STATUS_DELETED = 64;
    // Model
    public const ENTITY_STATUS_MODEL = 128;
    // Clone
    public const ENTITY_STATUS_CLONED = 256;
    public const ENTITY_STATUS_CLONING = 512;

    public function getType(): string;
    public function isEmptyStatus(): bool;
    // Dispatch requirements
    public static function getAvailableEvents(): array;
    public function isAvailableEvent(string $eventName): bool;
    public function getDispatchedEvents(): array;
    public function addDispatchedEvent(string $eventName, int $incValue = 1): static;
    public function resetDispatchedEvents(string|array $eventNames): static;
    public function requireDispatchEvent(string $eventName): bool;
    public function isEventDispatched(string $eventName): bool;
    // Entity
    public function isEntity(): bool;
    public function setCreated(): static;
    public function isCreated(): bool;
    public function setLoaded(): static;
    public function isLoaded(): bool;
    public function setPersisted(): static;
    public function isPersisted(): bool;
    public function setFlushed(): static;
    public function isFlushing(): bool;
    public function setPostflushed(): static;
    public function isFlushed(): bool;
    public function setTodeleted(): static;
    public function isTodelete(): bool;
    public function setDeleted(): static;
    public function isDeleted(): bool;
    // Manageable
    public function isManageable(): bool;
    public function failIfNotManageable(string $method = null, int $line = null, string $message = null): void;
    // Model
    public function setModel(): static;
    public function isModel(): bool;
    // Clone
    public function isClonable(): bool;
    public function setCloning(): static;
    public function isCloning(): bool;
    public function setCloned(): static;
    public function isCloned(): bool;

}