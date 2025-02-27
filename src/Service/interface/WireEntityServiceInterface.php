<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;

interface WireEntityServiceInterface extends WireServiceInterface, EntityServicePaginableInterface
{

    // Services
    public function getEntityManager(): EntityManagerInterface;
    public function getEm(): EntityManagerInterface;
    public function getUnitOfWork(): UnitOfWork;
    public function getUow(): UnitOfWork;
    // New
    public function createEntity(
        ?array $data = [], // ---> do not forget uname if wanted!
        ?array $context = []
    ): WireEntityInterface;
    public function createModel(
        ?array $data = [], // ---> do not forget uname if wanted!
        ?array $context = []
    ): WireEntityInterface;
    public function createClone(
        WireEntityInterface $entity,
        ?array $changes = [], // ---> do not forget uname if wanted!
        ?array $context = []
    ): WireEntityInterface|false;
    // Querys
    public function getEntityClassname(): ?string;
    public function getRepository($classname = null): ?EntityRepository;
    public function getEntitiesCount(array $criteria = [], $classname = null): int|false;

}