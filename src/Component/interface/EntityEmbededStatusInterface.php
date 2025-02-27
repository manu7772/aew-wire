<?php
namespace Aequation\WireBundle\Component\interface;

/**
 * Interface EntityEmbededStatusInterface
 * @package Aequation\WireBundle\Component\interface
 */
interface EntityEmbededStatusInterface
{

    // from AppWire service
    public function isDev(): bool;
    public function isProd(): bool;
    // Model
    public function setModel(): static;
    public function isModel(): bool;
    // UniOfWork functionalities
    public function isContained(): bool; // Is managed
    public function isEntityScheduled(): bool;
    public function isScheduledForDirtyCheck(): bool;
    public function isScheduledForInsert(): bool;
    public function isScheduledForUpdate(): bool;
    public function isScheduledForDelete(): bool;

}