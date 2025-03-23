<?php

namespace Aequation\WireBundle\Serializer;

use Aequation\WireBundle\Component\NormalizeDataContainer;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\NormalizerService;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\HttpRequest;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\AssociationMapping;

class EntityDenormalizer implements DenormalizerInterface
{

    public const ENABLED = true;

    public ?NormalizeDataContainer $currentContainer = null;
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

    public function isEnabled(): bool
    {
        return static::ENABLED;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        /** @var NormalizeDataContainer $data */
        $this->currentContainer = $data;
        $data = $this->currentContainer->getData();
        // dump($data);
        /** @var WireEntityInterface */
        $entity = $this->denormalizer->denormalize($data, $this->currentContainer->getType(), $format, $this->currentContainer->getDenormalizationContext());
        $this->currentContainer->setEntity($entity);
        // dd(PHP_EOL.'*** STOPPED on '.__METHOD__.' line '.__LINE__.' ***'.PHP_EOL, HttpRequest::isCli() ? $this->getNormaliserService()->normalizeEntity($entity, context: [AbstractNormalizer::GROUPS => NormalizerService::getNormalizeGroups($entity, 'debug')]) : $entity, $entity->getUnameThenEuid());
        // foreach ($this->currentContainer->getAssociationMappings() as $field => $mapping) {
        //     /** @var AssociationMapping $mapping */
        //     switch (true) {
        //         case $mapping->isToOne():
        //             // ToOne Relation
        //             if ($relatedEntity = $this->FindOrCreateEntity($data[$field], $mapping, $this->currentContainer, $format)) {
        //                 $this->currentContainer->setFieldValue($field, $relatedEntity);
        //             }
        //             break;
        //         case $mapping->isToMany():
        //             // ToMany Relation
        //             $relatedEntitys = new ArrayCollection();
        //             foreach ($data as $index => $value) {
        //                 if (is_array($value) && Encoders::isUnameFormatValid($index)) {
        //                     $value['uname'] ??= $index;
        //                 }
        //                 $relatedEntity = $this->FindOrCreateEntity($value, $mapping, $this->currentContainer, $format);
        //                 if ($relatedEntity && !$relatedEntitys->contains($relatedEntity)) {
        //                     $relatedEntitys->add($relatedEntity);
        //                 }
        //             }
        //             if (!$relatedEntitys->isEmpty()) $this->currentContainer->setFieldValue($field, $relatedEntitys);
        //             break;
        //     }
        // }

        return $entity;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        $supports = $this->isEnabled()
            && $data instanceof NormalizeDataContainer
            // && is_a($type, WireEntityInterface::class, true)
            // && !is_a($type, UnameInterface::class, true)
            ;
        return $supports;
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->isEnabled() ? [WireEntityInterface::class => true] : [];
    }


    /****************************************************************************************************/
    /** INTERNALS                                                                                       */
    /****************************************************************************************************/

    // private function FindOrCreateEntity(
    //     iterable|int|string $data,
    //     AssociationMapping $mapping,
    //     NormalizeDataContainer $container,
    //     ?string $format = null
    // ): ?WireEntityInterface {
    //     if(is_scalar($data)) {
    //         if(Encoders::isEuidFormatValid($data)) {
    //             $data = ['euid' => $data];
    //         } else if(Encoders::isUnameFormatValid($data)) {
    //             $data = ['uname' => $data];
    //         } else if(preg_match('/^\d+$/', (string)$data) && intval($data) > 0) {
    //             $data = ['id' => intval($data)];
    //         } else {
    //             return null;
    //         }
    //     }
    //     /** @var NormalizeDataContainer $nc */
    //     $nc = new NormalizeDataContainer($this->wireEm, $mapping->targetEntity, $data, create_only: $mapping->orphanRemoval, is_model: $container->isModel());
    //     $entity = $this->getNormaliserService()->denormalizeEntity($nc, $nc->getType(), $format);
    //     return $entity;
    // }
}
