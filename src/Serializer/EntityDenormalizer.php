<?php

namespace Aequation\WireBundle\Serializer;

use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\NormalizerService;
use Aequation\WireBundle\Tools\Encoders;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ToOneAssociationMapping;
use Doctrine\ORM\Mapping\ToManyAssociationMapping;

class EntityDenormalizer implements DenormalizerInterface
{

    public const ENABLED = true;
    public const NOT_CONTROLLED_CLASSES = [
        Uname::class,
    ];
    public const SEARCH_KEYS = ['id', 'slug', 'euid'];

    public array $currentContext = [];
    public bool $currentIsModel = false;
    private readonly NormalizerServiceInterface $normService;

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly DenormalizerInterface $denormalizer,
        private readonly WireEntityManagerInterface $wireEm
    ) {}

    public function getNormaliserService(): NormalizerServiceInterface
    {
        return $this->normService ??= $this->wireEm->getNormaliserService();
    }

    public static function isEnabled(): bool
    {
        return static::ENABLED;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $this->currentContext = $context;
        // Check real/model entity
        if (isset($this->currentContext[NormalizerService::CONTEXT_AS_MODEL])) {
            $this->currentIsModel = $this->currentContext[NormalizerService::CONTEXT_AS_MODEL];
            unset($this->currentContext[NormalizerService::CONTEXT_AS_MODEL]);
            dd($this->currentContext, $this->currentIsModel);
        }
        // Existing entity to populate
        if ($existing_entity = $this->getNormaliserService()->cleanAndPrepareDataToDeserialize($data, $type)) {
            if (empty($this->currentContext[AbstractNormalizer::OBJECT_TO_POPULATE] ?? null)) {
                $this->currentContext[AbstractNormalizer::OBJECT_TO_POPULATE] = $existing_entity;
            }
        }
        // If MODEL
        if ($this->currentIsModel) {
            if (isset($this->currentContext[AbstractNormalizer::OBJECT_TO_POPULATE]) && $this->currentContext[AbstractNormalizer::OBJECT_TO_POPULATE] instanceof WireEntityInterface) {
                $this->currentContext[AbstractNormalizer::OBJECT_TO_POPULATE] = $this->wireEm->createClone($this->currentContext[AbstractNormalizer::OBJECT_TO_POPULATE]);
            }
        }

        /** @var WireEntityInterface */
        $entity = $this->denormalizer->denormalize($data, $type, $format, $this->currentContext);

        $cmd = $this->wireEm->getClassMetadata($entity->getClassname());
        foreach ($cmd->getAssociationMappings() as $field => $relation) {
            $target_entity = $relation['targetEntity'];
            if (!empty($data[$field] ?? null)) {
                // $this->loadRelated($entity, $field, $data[$field], $relation['targetEntity'], $cmd);
                switch (true) {
                    case $relation instanceof ToOneAssociationMapping:
                        // ToOne Relation
                        if ($relatedEntity = $this->FindOrCreateEntity($data[$field], $target_entity)) {
                            $cmd->setFieldValue($entity, $field, $relatedEntity);
                        }
                        break;
                    case $relation instanceof ToManyAssociationMapping:
                        // ToMany Relation
                        $relatedEntitys = new ArrayCollection();
                        foreach ($data as $index => $value) {
                            if (is_string($index) && is_array($value) && Uname::isValidUname($index)) {
                                $value['uname'] ??= $index;
                            }
                            $relatedEntity = $this->FindOrCreateEntity($value, $target_entity);
                            if ($relatedEntity && !$relatedEntitys->contains($relatedEntity)) {
                                $relatedEntitys->add($relatedEntity);
                            }
                        }
                        if (!$relatedEntitys->isEmpty()) $cmd->setFieldValue($entity, $field, $relatedEntitys);
                        break;
                }
            }
        }

        return $entity;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        $supports = static::isEnabled()
            && is_array($data)
            && is_a($type, WireEntityInterface::class, true)
            && !is_a($type, UnameInterface::class, true);
        return $supports;
    }

    public function getSupportedTypes(?string $format): array
    {
        return static::isEnabled() ? [WireEntityInterface::class => true] : [];
    }


    /****************************************************************************************************/
    /** INTERNALS                                                                                       */
    /****************************************************************************************************/

    private function FindOrCreateEntity(
        iterable|int|string $data,
        string $classname
    ): ?WireEntityInterface {
        if (Encoders::isEuidFormatValid($data)) {
            $data = ['euid' => $data];
        } else if (Uname::isValidUname($data)) {
            $data = ['uname' => $data];
        } else if (preg_match('/^\d+$/', (string)$data) && intval($data) > 0) {
            $data = ['id' => intval($data)];
        }
        $entity = $this->getNormaliserService()->denormalizeEntity($data, $classname);
        return $entity;
    }
}
