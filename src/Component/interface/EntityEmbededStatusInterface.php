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
    public function isDev(): bool;
    public function isProd(): bool;
    public function isSadmin(): bool;
    public function isAdmin(): bool;
    public function isDevOrSadmin(): bool;
    public function getOrphanRelations(): array;
    public function isContained(): bool;
    public function isEntityScheduled(): bool;
    public function isScheduledForDirtyCheck(): bool;
    public function isScheduledForInsert(): bool;
    public function isScheduledForUpdate(): bool;
    public function isScheduledForDelete(): bool;
}