<?php
namespace Aequation\WireBundle\Component\interface;

/**
 * Interface EntityEmbededStatusInterface
 * @package Aequation\WireBundle\Component\interface
 */
interface EntityEmbededStatusInterface
{

    // Entity status
    public const STATUS_NULL = 0;
    public const STATUS_CREATED = 1;
    public const STATUS_LOADED = 2;
    public const STATUS_CLONE = 4;
    public const STATUS_MODEL = 8;
    public const STATUS_FLUSHED = 16;
    public const STATUS_DELETED = 32;

    // from AppWire service
    public function isDev(): bool;
    public function isProd(): bool;
    // Status
    public static function getAllStatus(): array;
    public function getAllStatusAsString(string $separator = ', '): string;
    public function getStatus(): int;
    public function isEmptyStatus(): bool;
    // Entity
    public function setCreated(): static;
    public function isCreated(): bool;
    public function setLoaded(): static;
    public function isLoaded(): bool;
    public function isFlushed(): bool;
    public function setDeleted(): static;
    public function isDeleted(): bool;
    // Model
    public function setModel(): static;
    public function isModel(): bool;
    // Clone
    public function isClonable(): bool;
    public function setClone(): static;
    public function isClone(): bool;
    // UniOfWork functionalities
    public function isContained(): bool; // Is managed
    public function isPersisted(): bool; // Is in database
    public function isEntityScheduled(): bool;
    public function isScheduledForDirtyCheck(): bool;
    public function isScheduledForInsert(): bool;
    public function isScheduledForUpdate(): bool;
    public function isScheduledForDelete(): bool;
    // Dispatch requirements
    public function applyEvents(string|array $eventNames): static;
    public static function getAvailableEvents(): array;
    public function isAvailableEvent(string $eventName): bool;
    public function getDispatchedEvents(): array;
    public function addDispatchedEvent(string $eventName, int $incValue = 1): static;
    public function resetDispatchedEvents(string|array $eventNames = null): static;
    public function requireDispatchEvent(string $eventName): bool;
    public function isEventDispatched(string $eventName): bool;

}