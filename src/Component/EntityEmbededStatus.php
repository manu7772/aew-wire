<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Entity\interface\TraitClonableInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Event\WireEntityEvent;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
// Symfony
use Doctrine\ORM\UnitOfWork;
// PHP
use Exception;

/**
 * EntityEmbededStatus
 * Entity status container of useful methods and services
 */
class EntityEmbededStatus implements EntityEmbededStatusInterface
{

    public readonly WireEntityManagerInterface $wireEntityManager;
    public readonly WireEntityServiceInterface $service;
    public readonly EntityManagerInterface $em;
    public readonly UnitOfWork $uow;
    public readonly bool $model;

    /**
     * Constructor
     *
     * @param WireEntityInterface $entity
     * @param AppWireServiceInterface $appWire
     */
    public function __construct(
        public readonly WireEntityInterface $entity,
        public readonly AppWireServiceInterface $appWire
    )
    {
        $this->wireEntityManager = $this->appWire->get(WireEntityManagerInterface::class);
        $this->em = $this->wireEntityManager->getEm();
        $this->uow = $this->wireEntityManager->getUow();
        $service = $this->wireEntityManager->getEntityService($this->entity);
        $this->service = $service;
        $entity->setEmbededStatus($this);
    }

    /**
     * Is dev environment
     *
     * @return boolean
     */
    public function isDev(): bool
    {
        return $this->appWire->isDev();
    }

    /**
     * Is prod environment
     *
     * @return boolean
     */
    public function isProd(): bool
    {
        return $this->appWire->isProd();
    }


    /** MODEL */

    /**
     * Set entity status to MODEL
     *
     * @return static
     */
    public function setModel(): static
    {
        $this->model = true;
        return $this;
    }

    /**
     * Is entity model
     *
     * @return boolean
     */
    public function isModel(): bool
    {
        return $this->model ?? false;
    }


    /** UniOfWork functionalities */

    /**
     * Is managed by EntityManager
     *
     * @return boolean
     */
    public function isContained(): bool
    {
        return $this->em->contains($this->entity);
    }

    /**
     * Is entity scheduled for operations
     *
     * @return boolean
     */
    public function isEntityScheduled(): bool
    {
        return $this->uow->isEntityScheduled($this->entity);
    }

    /**
     * Is entity scheduled for dirty check
     *
     * @return boolean
     */
    public function isScheduledForDirtyCheck(): bool
    {
        return $this->uow->isScheduledForDirtyCheck($this->entity);
    }

    /**
     * Is entity scheduled for insert
     *
     * @return boolean
     */
    public function isScheduledForInsert(): bool
    {
        return $this->uow->isScheduledForInsert($this->entity);
    }

    /**
     * Is entity scheduled for update
     *
     * @return boolean
     */
    public function isScheduledForUpdate(): bool
    {
        return $this->uow->isScheduledForUpdate($this->entity);
    }

    public function isScheduledForDelete(): bool
    {
        return $this->uow->isScheduledForDelete($this->entity);
    }


}