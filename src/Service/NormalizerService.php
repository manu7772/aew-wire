<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Serializer\EntityDenormalizer;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
// Symfony
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
// PHP
use ArrayObject;

/**
 * Normalizer service
 * @see https://symfony.com/doc/current/serializer.html
 */
#[AsAlias(NormalizerServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class NormalizerService implements NormalizerServiceInterface
{
    use TraitBaseService;

    public function __construct(
        public readonly SerializerInterface $serializer
    ) {
    }

    /****************************************************************************************************/
    /** NORMALIZER                                                                                      */
    /****************************************************************************************************/

    public function normalize(
        mixed $data,
        ?string $format = null,
        ?array $context = [],
        ?bool $convertToArrayList = false
    ): array|string|int|float|bool|ArrayObject|null
    {
        if($data instanceof Collection) $data = $data->toArray();
        if($convertToArrayList && is_array($data) && !array_is_list($data)) $data = array_values($data); // for React, can not be object, but array
        /** @var NormalizerInterface */
        $normalizer = $this->serializer;
        return $normalizer->normalize($data, $format, $context);
    }

    public function denormalize(
        mixed $data,
        string $type,
        ?string $format = null,
        ?array $context = []
    ): mixed
    {
        /** @var DenormalizerInterface */
        $denormalizer = $this->serializer;
        return $denormalizer->denormalize($data, $type, $format, $context);
    }

    /****************************************************************************************************/
    /** NORMALIZER ENTITY                                                                               */
    /****************************************************************************************************/

    public function normalizeEntity(
        WireEntityInterface $entity,
        ?string $format = null,
        ?array $context = []
    ): array|string|int|float|bool|ArrayObject|null
    {
        if(empty($context['groups'] ?? [])) {
            $context['groups'] = EntityDenormalizer::getNormalizeGroups($entity);
        }
        // $context['groups'] = array_merge($context['groups'] ?? [], EntityDenormalizer::getNormalizeGroups($entity));
        return $this->normalize($entity, $format, $context);
    }

    public function denormalizeEntity(
        mixed $data,
        string $type,
        ?string $format = null,
        ?array $context = []
    ): WireEntityInterface
    {
        if(empty($context['groups'] ?? [])) {
            $context['groups'] = EntityDenormalizer::getNormalizeGroups($type);
        }
        // $context['groups'] = array_merge($context['groups'] ?? [], EntityDenormalizer::getNormalizeGroups($type));
        /** @var DenormalizerInterface */
        $denormalizer = $this->serializer;
        return $denormalizer->denormalize($data, $type, $format, $context);
    }

    /****************************************************************************************************/
    /** SERIALIZER                                                                                      */
    /****************************************************************************************************/

    public function serialize(
        mixed $data,
        string $format,
        ?array $context = [],
        ?bool $convertToArrayList = false
    ): string
    {
        if($data instanceof Collection) $data = $data->toArray();
        if($convertToArrayList && is_array($data) && !array_is_list($data)) $data = array_values($data); // for React, can not be object, but array
        return $this->serializer->serialize($data, $format, $context);
    }

    public function deserialize(
        string $data,
        string $type,
        string $format,
        ?array $context = []
    ): mixed
    {
        return $this->serializer->deserialize($data, $type, $format, $context);
    }


}