<?php

namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\PaginatedContextDataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Dto\interface\WireEntityDtoInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use Knp\Component\Pager\Pagination\PaginationInterface;

interface WireEntityServiceInterface extends WireServiceInterface, EntityServicePaginableInterface
{

    public const ENTITY_CLASS = BaseEntityInterface::class;

    // Services
    public function getWireEm(): WireEntityManagerInterface;
    public function getEntityManager(): EntityManagerInterface;
    public function getWireClassMetadata(): ?WireClassMetadataInterface;
    public function getEm(): EntityManagerInterface;
    public function getUnitOfWork(): UnitOfWork;
    public function getUow(): UnitOfWork;
    // Check actions
    public function entityEventActions(BaseEntityInterface $entity, ?OpresultInterface $opresult = null): void;
    public function entityCheckActions(BaseEntityInterface $entity, ?OpresultInterface $opresult = null, bool $repair = false): void;
    public function checkDatabase(OpresultInterface $opresult, bool $repair = false, array $options = []): void;
    // Create
    public function createEntity(array $data = [], array $options = []): object;
    public function createModel(array $data = [], array $options = []): BaseEntityInterface;
    public function createClone(BaseEntityInterface $entity, array $changes = [], array $options = []): BaseEntityInterface|false;
    public function createDto(array $data = [], array $options = []): ?WireEntityDtoInterface;
    // Informations
    public static function getEntityClassname(): string;
    public function getEntityShortname(): string;
    public function getDtoClassnames(): array;
    public function getEntityType(): string;
    public function getRepository(?string $classname = null): ?EntityRepository;
    // Pagination
    public function paginatedAction(Closure $callback, ?string $method = null, array $parameters = [], array $options = []): void;
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
    public function count(
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
    public function findOneBy(
        null|int|string $identifier = null,
        bool|array $criteria = [],
        ?array $orderBy = null
    ): ?object;

    // Criteria
    public static function getCriteriaEnabled(): array;
    public static function getCriteriaDisabled(): array;
}
