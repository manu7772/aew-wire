<?php

namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;

interface WireEntityServiceInterface extends WireServiceInterface, EntityServicePaginableInterface
{

    public const ENTITY_CLASS = WireEntityInterface::class;

    // Services
    public function getEntityManager(): EntityManagerInterface;
    public function getEm(): EntityManagerInterface;
    public function getUnitOfWork(): UnitOfWork;
    public function getUow(): UnitOfWork;
    // public function checkEntity(WireEntityInterface $entity): void;
    // New
    public function createEntity(
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = []
    ): WireEntityInterface;
    public function createModel(
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = []
    ): WireEntityInterface;
    public function createClone(
        WireEntityInterface $entity,
        array $changes = [], // ---> do not forget uname if wanted!
        array $context = []
    ): WireEntityInterface|false;
    // Maintain database
    public function checkDatabase(?OpresultInterface $opresult = null, bool $repair = false): OpresultInterface;
    // Querys
    public function getEntityClassname(): string;
    public function getRepository(?string $classname = null): ?EntityRepository;
    public function getEntitiesCount(array $criteria = [], ?string $classname = null): int|false;
}
