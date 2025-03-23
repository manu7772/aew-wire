<?php
namespace Aequation\WireBundle\Component\interface;


interface EntitySelfStateInterface
{
    public const STATES = [
        'new'       => 0b00000001,
        'loaded'    => 0b00000010,
        'persisted' => 0b00000100,
        'updated'   => 0b00001000,
        'removed'   => 0b00010000,
        'detached'  => 0b00100000,
        'model'     => 0b01000000,
    ];

    public const POST_CREATED   = 0b00000001;
    public const POST_LOADED    = 0b00000010;
    public const POST_PERSISTED = 0b00000100;
    public const POST_UPDATED   = 0b00001000;

    public function getReport(bool $asString = false): array|string;
    public function isDebug(): bool;
    public function setNew(): static;
    public function isNew(): bool;
    public function setLoaded(): static;
    public function isLoaded(): bool;
    public function setPersisted(): static;
    public function isPersisted(): bool;
    public function setUpdated(): static;
    public function isUpdated(): bool;
    public function setRemoved(): static;
    public function isRemoved(): bool;
    public function setDetached(): static;
    public function isDetached(): bool;
    public function isEntity(): bool;
    public function setModel(): static;
    public function isModel(): bool;

    // Events
    public function eventDone(string $bin): bool;
    public function setPostCreated(): static;
    public function isPostCreated(): bool;
    public function setPostLoaded(): static;
    public function isPostLoaded(): bool;

}