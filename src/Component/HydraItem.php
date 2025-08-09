<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\HydradataItemsInterface;
use Aequation\WireBundle\Component\interface\HydraItemInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Dto\interface\WireEntityDtoInterface;
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
    public readonly HydrationServiceInterface $hydrator;
    public readonly ObjectMapperInterface $objectMapper;

    public function __construct(
        array $data,
        protected HydradataItemsInterface $hydradataItems,
        public readonly ?int $item_index = null
    ) {
        $this->elements = $data;
        $this->wireEm = $this->hydradataItems->wireEm;
        $this->hydrator = $this->wireEm->appWire->get(HydrationServiceInterface::class);
        $this->objectMapper = $this->hydrator->getObjectMapper();
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

    public function getHydradataItems(): HydradataItemsInterface
    {
        return $this->hydradataItems;
    }

    public function getWcmd(): WireClassMetadataInterface|false
    {
        return $this->hydradataItems->getWcmd();
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
        $entity = $this->getPersistedOrNew();
        $dto = $this->createDto($entity);
        // dd($dto, $entity);
        return $this->objectMapper->map($dto, $entity);
    }

    public function getPersistedOrNew(): ?object
    {
        return $this->persisted ?? $this->getNew();
    }

    public function getPersisted(): ?object
    {
        return $this->persisted;
    }

    public function hasPersisted(): bool
    {
        return $this->persisted instanceof BaseEntityInterface;
    }

    public function createDto(array|object $pre_set_data = []): ?object
    {
        // dump($pre_set_data);
        if(is_object($pre_set_data)) {
            $pre_set_data = $pre_set_data instanceof WireEntityDtoInterface ? $pre_set_data->toArray() : $this->hydrator->getDto($pre_set_data)->toArray();
        }
        // dump($pre_set_data);
        $dto = $this->wireEm->createDto($this->name, $pre_set_data);
        // dump($dto, $this->toArray());
        $dto->insertData($this->toArray());
        // dump($dto);
        return $dto;
    }

    public function getNew(): ?object
    {
        return $this->wireEm->getEntitiesMetadata()->newInstance($this->name);
    }

    // public static function mergeData(array $pre_set_data, array $data): array
    // {
    //     $adds = ['~'];
    //     $keys = array_keys($data);
    //     foreach ($adds as $add) {
    //         foreach ($keys as $key) {
    //             $keys[] = $add.$key;
    //         }
    //     }
    //     $merged = array_merge(array_filter($pre_set_data, fn ($key) => !in_array($key, $keys), ARRAY_FILTER_USE_KEY), $data);
    //     dd($keys, $pre_set_data, $data, $merged);
    //     return $merged;
    //     // return array_merge($pre_set_data, $data);
    // }

}