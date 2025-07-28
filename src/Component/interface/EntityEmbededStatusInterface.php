<?php
namespace Aequation\WireBundle\Component\interface;

// Symfony
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Interface EntityEmbededStatusInterface
 * @package Aequation\WireBundle\Component\interface
 */
interface EntityEmbededStatusInterface extends EntityEmbededStatusContainerInterface
{
    public function getAppWire(): AppWireServiceInterface;
    public function getWireEm(): WireEntityManagerInterface;
    public function getEm(): EntityManagerInterface;
    public function getService(): WireEntityServiceInterface;
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