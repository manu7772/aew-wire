<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Component\interface\EntitySelfStateInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
// PHP
use Exception;
use BadMethodCallException;
use Dom\Entity;

/**
 * EntityEmbededStatus
 * Entity status container of useful methods and services
 */
class EntityEmbededStatus implements EntityEmbededStatusInterface
{
    public readonly WireEntityManagerInterface $wireEm;
    public readonly WireEntityServiceInterface $service;
    public readonly EntityManagerInterface $em;
    public readonly UnitOfWork $uow;
    public readonly BaseEntityInterface $entity;

    /**
     * Constructor
     *
     * @param BaseEntityInterface $entity
     * @param AppWireServiceInterface $appWire
     */
    public function __construct(
        public readonly EntitySelfStateInterface $selfstate,
        public readonly AppWireServiceInterface $appWire
    ) {
        $this->entity = $this->selfstate->entity;
        $this->wireEm = $this->appWire->get(WireEntityManagerInterface::class);
        $this->em = $this->wireEm->getEm();
        $this->uow = $this->wireEm->getUow();
        $this->service = $this->wireEm->getEntityService($this->entity);

        // Some controls...
        if($this->selfstate->isLoaded() && !$this->isContained()) {
            throw new Exception(vsprintf('Error %s line %d: entity %s looks %s, but not contained in EntityManager!', [__METHOD__, __LINE__, $this->entity->getClassname(), $this->selfstate->isNew() ? 'new' : 'loaded']));
        }
    }


    /*******************************************************************************************
     * MAGIC METHODS on EntitySelfState
     */

    // public function __call(string $name, array $arguments): mixed
    // {
    //     if (method_exists($this->selfstate, $name)) {
    //         return $this->selfstate->$name(...$arguments);
    //     }
    //     throw new BadMethodCallException(vsprintf('Error %s line %d: method %s not found!', [__METHOD__, __LINE__, $name]));
    // }


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

    /**
     * Is super admin
     *
     * @return boolean
     */
    public function isSadmin(): bool
    {
        return $this->appWire->isGranted('ROLE_SUPER_ADMIN');
    }

    /**
     * Is admin
     *
     * @return boolean
     */
    public function isAdmin(): bool
    {
        return $this->appWire->isGranted('ROLE_ADMIN');
    }

    /**
     * Is Dev or super admin
     *
     * @return boolean
     */
    public function isDevOrSadmin(): bool
    {
        return $this->appWire->isDev() || $this->isSadmin();
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
