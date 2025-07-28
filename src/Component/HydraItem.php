<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\HydradataItemsInterface;
use Aequation\WireBundle\Component\interface\HydraItemInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\HydrationServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\EntityRepository;
use Exception;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class HydraItem extends TypedCollection implements HydraItemInterface
{

    public readonly string $name;
    protected ?object $persisted = null;
    public readonly EntityRepository $repo;
    public readonly WireEntityManagerInterface $wireEm;
    public readonly ObjectMapperInterface $objectMapper;

    public function __construct(
        array $data,
        protected HydradataItemsInterface $hydradataItems,
        public readonly ?int $item_index = null
    ) {
        $this->elements = $data;
        $this->wireEm = $this->hydradataItems->wireEm;
        $this->objectMapper = $this->wireEm->appWire->get(HydrationServiceInterface::class)->getObjectMapper();
        $this->name = $this->hydradataItems->name;
        // Try find entity in database
        $this->repo = $this->wireEm->getRepository($this->name);
        $this->update(true);
    }

    public function __toString(): string
    {
        return static::class.'@'.spl_object_hash($this).'@'.$this->name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function update(bool $update_persisted = false): void
    {
        if($update_persisted) {
            $this->persisted = null;
        }
        // Try ID
        if(empty($this->persisted) && is_int($this->elements['id'] ?? null) && $this->elements['id'] ?? null > 0) {
            $this->persisted = $this->repo->find($this->elements['id']);
        }
        if(empty($this->persisted) && !empty($this->elements['euid'] ?? null)) {
            $this->persisted = $this->repo->findOneOrNullBy(['euid' => $this->elements['euid']]);
        }
        if(empty($this->persisted) && !empty($this->elements['uname'] ?? null)) {
            $this->persisted = $this->wireEm->findEntityByUname($this->elements['uname']);
        }
    }

    public function getHydratedEntity(): ?object
    {
        $entity = $this->hasPersisted() ? $this->getPersisted() : $this->getNew();
        if($this->hasPersisted() && $entity->getSelfState()->isNew()) {
            throw new Exception(vsprintf('Error %s line %d: the entity %s is not persisted. Please persist it before accessing it.', [__METHOD__, __LINE__, Objects::toDebugString($this->persisted)]));
        }
        return $this->objectMapper->map($this->createDto(), $entity);
    }

    public function getPersisted(): ?object
    {
        return $this->persisted;
    }

    public function hasPersisted(): bool
    {
        return $this->persisted instanceof BaseEntityInterface;
    }

    public function createDto(array $over_data = []): ?object
    {
        return $this->wireEm->createDto($this->name, array_merge($this->toArray(), $over_data));
    }

    public function getNew(): ?object
    {
        return $this->wireEm->getEntitiesMetadata()->newInstance($this->name);
    }

}