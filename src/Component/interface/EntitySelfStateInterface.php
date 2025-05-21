<?php
namespace Aequation\WireBundle\Component\interface;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;

/**
 * Interface EntitySelfStateInterface
 * @package Aequation\WireBundle\Component\interface
 * 
 * @method EntityEmbededStatusInterface
 */
interface EntitySelfStateInterface extends EntityEmbededStatusContainerInterface
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

    public function __construct(
        BaseEntityInterface $entity
    );

    public const POST_CREATED   = 0b00000001;
    public const POST_LOADED    = 0b00000010;
    public const POST_PERSISTED = 0b00000100;
    public const POST_UPDATED   = 0b00001000;

    /**
     * Contains service, but not necessarily started yet
     */
    public function isReady(): bool;
    /**
     * Contains service and is started
     */
    public function isStarted(): bool;
    public function startEmbed(AppWireServiceInterface $appWire, bool $startNow = false): bool;
    public function getEmbededStatus(): ?EntityEmbededStatusInterface;
    public function __call(string $name, array $arguments): mixed;
    // status
    public function isExactBinState(int $state): bool;
    public function isExactState(string $state): bool;
    public function isNew(): bool;
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
    public function applyEvents(): void;
    public function eventDone(string $bin): bool;
    public function setPostCreated(): static;
    public function isPostCreated(): bool;
    public function setPostLoaded(): static;
    public function isPostLoaded(): bool;
    // Report
    public function getReport(bool $asString = false): array|string;

}