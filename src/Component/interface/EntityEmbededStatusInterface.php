<?php
namespace Aequation\WireBundle\Component\interface;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;

/**
 * Interface EntityEmbededStatusInterface
 * @package Aequation\WireBundle\Component\interface
 */
interface EntityEmbededStatusInterface extends EntityEmbededStatusContainerInterface
{
    // from AppWire service
    public function isDev(): bool;
    public function isProd(): bool;
    public function isSadmin(): bool;
    public function isAdmin(): bool;
    public function isDevOrSadmin(): bool;
    // UniOfWork functionalities
    public function isContained(): bool; // Is managed
    public function isEntityScheduled(): bool;
    public function isScheduledForDirtyCheck(): bool;
    public function isScheduledForInsert(): bool;
    public function isScheduledForUpdate(): bool;
    public function isScheduledForDelete(): bool;

}