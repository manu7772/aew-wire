<?php

namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;

interface WireEntityServiceInterface extends WireServiceInterface, EntityServicePaginableInterface
{

    public const ENTITY_CLASS = BaseEntityInterface::class;

    // Services
    public function getEntityManager(): EntityManagerInterface;
    public function getEm(): EntityManagerInterface;
    public function getUnitOfWork(): UnitOfWork;
    public function getUow(): UnitOfWork;
    // public function checkEntity(BaseEntityInterface $entity): void;
    // New
    public function createEntity(
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = []
    ): BaseEntityInterface;
    public function createModel(
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = []
    ): BaseEntityInterface;
    public function createClone(
        BaseEntityInterface $entity,
        array $changes = [], // ---> do not forget uname if wanted!
        array $context = []
    ): BaseEntityInterface|false;
    // Maintain database
    public function checkDatabase(?OpresultInterface $opresult = null, bool $repair = false): OpresultInterface;
    // Querys
    public static function getEntityClassname(): string;
    public function getEntityShortname(): string;
    public function getRepository(?string $classname = null): ?EntityRepository;
    // Find
    /**
     * get entities count
     * - uses criteria
     * - search *ONLY IN DATABASE*
     * - if `$criteria` is boolean, it will be converted to criteria: true = active, false = inactive
     * 
     * - ~barré~
     * - `code`
     * - *italic*
     * 
     * @param bool|array $criteria
     * @return int
     */
    public function getCount(
        bool|array $criteria = []
    ): int;
    /**
     * get all entities
     * - uses criteria
     * - search *ONLY IN DATABASE*
     * - if `$criteria` is boolean, it will be converted to criteria: true = active, false = inactive
     * 
     * - ~barré~
     * - `code`
     * - *italic*
     * 
     * @param bool|array $criteria
     * @return array
     */
    public function findAll(
        bool|array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array;
    /**
     * get one entity by id or euid or uname
     * - uses criteria
     * - search *ONLY IN DATABASE*
     * - if `$criteria` is boolean, it will be converted to criteria: true = active, false = inactive
     * 
     * - ~barré~
     * - `code`
     * - *italic*
     * 
     * @param int|string $identifier
     * @param bool|array $criteria
     * @return object|null
     */
    public function find(
        int|string $identifier,
        bool|array $criteria = [],
        ?array $orderBy = null
    ): ?object;

    // Criteria
    public static function getCriteriaEnabled(): array;
    public static function getCriteriaDisabled(): array;
}
