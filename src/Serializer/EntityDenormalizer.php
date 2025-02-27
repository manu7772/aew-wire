<?php
namespace Aequation\WireBundle\Serializer;

use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Encoders;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Doctrine\ORM\Mapping\ToOneAssociationMapping;
use Doctrine\ORM\Mapping\ToManyAssociationMapping;
// PHP
use Exception;
use ReflectionClass;

class EntityDenormalizer implements DenormalizerInterface
{

    public const ENABLED = true;
    public const NORMALIZATION_GROUPS = [
        'hydrate' => [
            'normalize' => ['identifier','__shortname__.hydrate','hydrate'],
            'denormalize' => ['__shortname__.hydrate','hydrate'],
        ],
        'model' => [
            'normalize' => ['identifier','__shortname__.model','model'],
            'denormalize' => ['__shortname__.model','model'],
        ],
        'clone' => [
            'normalize' => ['identifier','__shortname__.clone','clone'],
            'denormalize' => ['__shortname__.clone','clone'],
        ],
    ];
    public const SEARCH_KEYS = ['id','slug','euid'];

    public readonly EntityManagerInterface $em;
    public readonly WireEntityManagerInterface $wire_em;
    public array $currentContext = [];

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly DenormalizerInterface $denormalizer,
        private readonly AppWireServiceInterface $appWire
    ) {}

    public static function isEnabled(): bool
    {
        return static::ENABLED;
    }

    public function getWireEntityManager(): WireEntityManagerInterface
    {
        return $this->wire_em ??= $this->appWire->get(WireEntityManagerInterface::class);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em ??= $this->getWireEntityManager()->getEntityManager();
    }

    /**
     * Get normalization groups for a class
     * returns array of 2 named elements:
     *      - ["normalize" => <normalization groups>]
     *      - ["denormalize" => <denormalization groups>]
     * @param string|WireEntityInterface $class
     * @param string $type
     * @return array
     */
    public static function getNormalizeGroups(
        string|WireEntityInterface $class,
        string $type = 'hydrate', // ['hydrate','model','clone']
    ): array
    {
        if($class instanceof WireEntityInterface) {
            $class = $class->getClassname();
        }
        if(class_exists($class) && is_a($class, WireEntityInterface::class, true)) {
            $rc = new ReflectionClass($class);
            $class = $rc->getShortName();
        } else {
            throw new Exception(vsprintf('Error %s line %d: Class %s not found or not instance of %s!', [__METHOD__, __LINE__, $class, WireEntityInterface::class]));
        }
        $type = static::NORMALIZATION_GROUPS[$type];
        $groups = [
            'normalize' => [],
            'denormalize' => [],
        ];
        foreach ($type as $name => $values) {
            foreach ($values as $group_name) {
                $groups[$name][] = str_replace('__shortname__', strtolower($class), $group_name);
            }
        }
        return $groups;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $this->currentContext = $context;
        if(is_array($data) && is_a($type, WireEntityInterface::class, true)) {
            /** @var WireEntityInterface */
            $entity = $this->denormalizer->denormalize($data, $type, $format, $this->currentContext);
            if($entity instanceof TraitUnamedInterface && empty($entity->getUname())) {
                // dump($entity->getUnameName());
                throw new Exception(vsprintf('Error %s line %d: Entity %s has no Uname!', [__METHOD__, __LINE__, $entity->getShortname()]));
            }
            $cmd = $this->getEntityManager()->getClassMetadata($entity->getClassname());
            foreach ($cmd->getAssociationMappings() as $field => $relation) {
                if(!empty($data[$field] ?? null)) {
                    $this->loadRelated($entity, $field, $data[$field], $relation['targetEntity'], $cmd);
                }
            }
            return $entity;
        }
        return $this->denormalizer->denormalize($data, $type, $format, $this->currentContext);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return static::isEnabled()
            ? is_a($type, WireEntityInterface::class, true)
            // && isset($data['classname'])
            : false;
    }

    public function getSupportedTypes(?string $format): array
    {
        return static::isEnabled() ? [WireEntityInterface::class => true] : [];
    }

    protected function loadRelated(
        WireEntityInterface $entity,
        string $field,
        WireEntityInterface|iterable|int|string $data,
        string $target_class,
        ClassMetadata $cmd
    ): void
    {
        $cmd = $this->getEntityManager()->getClassMetadata($entity->getClassname());
        $relation = $cmd->getAssociationMapping($field);
        switch (true) {
            case $relation instanceof ToOneAssociationMapping:
                // ToOne Relation
                if($relatedEntity = $this->FindOrCreateOneEntity($data, $target_class)) {
                    $cmd->setFieldValue($entity, $field, $relatedEntity);
                }
                break;
            case $relation instanceof ToManyAssociationMapping:
                // ToMany Relation
                if(!is_iterable($data)) {
                    throw new Exception(vsprintf('Error %s line %d: ToMany relation field %s needs iterable data, got %s!', [__METHOD__, __LINE__, $field, gettype($data)]));
                }
                $relatedEntitys = new ArrayCollection();
                foreach ($data as $key => $value) {
                    if(is_string($key) && is_array($value) && !array_is_list($value)) {
                        $value['uname'] ??= $key; // --> Key can be the Uname
                    }
                    if($relatedEntity = $this->FindOrCreateOneEntity($value, $target_class)) {
                        if(!$relatedEntitys->contains($relatedEntity)) $relatedEntitys->add($relatedEntity);
                    }
                }
                if(!$relatedEntitys->isEmpty()) $cmd->setFieldValue($entity, $field, $relatedEntitys);
                break;
        }
    }

    public function FindOrCreateOneEntity(
        WireEntityInterface|iterable|int|string $identifier,
        string $target_class
    ): ?WireEntityInterface
    {
        $relatedEntity = null;
        // Try find just created entity in memory
        if(is_string($identifier) && $justcreated = $this->getWireEntityManager()->findCreated($identifier)) {
            $relatedEntity = $justcreated;
        } else {
            $target_repo = $this->getEntityManager()->getRepository($target_class);
            switch (true) {
                case $identifier instanceof WireEntityInterface:
                    // Is already entity
                    $relatedEntity = $identifier;
                    break;
                case Encoders::isEuidFormatValid($identifier):
                    // Load related entity by Euid
                    $relatedEntity = $target_repo->findOneBy(['euid' => $identifier]);
                    break;
                case is_int($identifier):
                    if($test = $target_repo->findOneBy(['id' => $identifier])) {
                        $relatedEntity = $test;
                    }
                    break;
                case is_string($identifier):
                    // Load related entity different searchs
                    foreach (static::SEARCH_KEYS as $key) {
                        if($test = $target_repo->findOneBy([$key => $identifier])) {
                            $relatedEntity = $test;
                            break;
                        }
                    }
                    if(empty($relatedEntity)) {
                        // Load related entity by uname or Euid
                        /** @var Uname */
                        $uname = $this->getEntityManager()->getRepository(Uname::class)->findOneBy(['id' => $identifier]);
                        if(!empty($uname)) {
                            $relatedEntity = $target_repo->findOneBy(['euid' => $uname->getEntityEuid()]);
                        }
                    }
                    break;
                case is_array($identifier):
                    // Load related entity by search key
                    $id_keys = array_intersect(static::SEARCH_KEYS, array_keys($identifier));
                    if(count($id_keys) > 0) {
                        foreach ($id_keys as $key) {
                            if($test = $this->FindOrCreateOneEntity($identifier[$key], $target_class)) {
                                $relatedEntity = $test;
                                break;
                            }
                        }
                    } else {
                        // No identifier found: create new Entity
                        $relatedEntity = $this->getWireEntityManager()->createEntity($target_class, $identifier, $this->currentContext);
                    }
                    break;
            }
        }
        $this->controlEntity($relatedEntity, $target_class, true);
        return $relatedEntity;
    }

    public function controlEntity(
        ?WireEntityInterface $entity,
        string $target_class,
        bool $exception = true
    ): bool
    {
        if(empty($entity)) {
            if($exception) throw new Exception(vsprintf('Error %s line %d: Entity of class %s not found!', [__METHOD__, __LINE__, $target_class]));
            return false;
        }
        if(!$entity->__estatus->isContained()) {
            if($exception) throw new Exception(vsprintf('Error %s line %d: Entity %s "%s" is not managed by EntityManager!', [__METHOD__, __LINE__, $entity->getShortname(), $entity->__toString()]));                    
            return false;
        }
        if(!is_a($entity, $target_class)) {
            if($exception) throw new Exception(vsprintf('Error %s line %d: Entity %s "%s" is not instance of %s!', [__METHOD__, __LINE__, $entity->getShortname(), $entity->__toString(), $target_class]));
            return false;
        }
        return true;
    }

    /**
     * Test if $id is set and is an integer or a string of numbers only.
     * If yes, set $id to integer.
     * @param mixed $id
     * @return boolean
     */
    public static function isIdsoSetInt(
        mixed &$id
    ): bool
    {
        if(is_int($id) && $id > 0) return true;
        if(is_string($id) && preg_match('/^\d+$/', $id)) {
            $id2 = (int)$id;
            if($id2 > 0) {
                $id = $id2;
                return true;
            }
        }
        return false;
    }

    /**
     * Test if $data is an array of managed entity.
     * @param array|int $data
     * @return boolean
     */
    protected function arrayIsAManagedEntity(
        array|int $data
    ): bool
    {
        if(static::isIdsoSetInt($data)) return true;
        $keys = is_array($data) ? array_keys($data) : ['id'];
        $has_indexes = count(array_intersect(static::SEARCH_KEYS, $keys)) > 0;
        if($has_indexes) {
            foreach (static::SEARCH_KEYS as $key) {
                if(!empty($data[$key] ?? null)) return true;
            }
        }
        return false;
    }

}