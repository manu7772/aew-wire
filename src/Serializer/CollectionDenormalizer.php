<?php
namespace Aequation\WireBundle\Serializer;

use Aequation\WireBundle\Component\interface\NormalizeDataContainerInterface;
use Aequation\WireBundle\Component\NormalizeDataContainer;
use Aequation\WireBundle\Tools\Objects;
use Doctrine\Common\Collections\ArrayCollection;
// Symfony
use Doctrine\Common\Collections\Collection;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CollectionDenormalizer implements DenormalizerInterface
{

    public const ENABLED = false;

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly DenormalizerInterface $denormalizer,
        // private readonly WireEntityManagerInterface $wireEm
    ) {}


    public function isEnabled(): bool
    {
        return static::ENABLED;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // dump($data);
        $shouldBeCollection = false;
        foreach ($data as $key => $value) {
            if($value instanceof NormalizeDataContainerInterface) {
                $shouldBeCollection = true;
                $entity = $this->denormalizer->denormalize($value->getData(), $value->getType(), $format, $value->getDenormalizationContext());
                if($entity) {
                    $value->finalizeEntity($entity);
                    if($data instanceof Collection) {
                        $data->set($key, $entity);
                    } else {
                        $data[$key] = $entity;
                    }
                } else {
                    throw new Exception(vsprintf('Error %s line %d: Entity with data %s not found!', [__METHOD__, __LINE__, Objects::toDebugString($value->getData())]));
                }
            }
        }
        if($shouldBeCollection && !($data instanceof Collection)) {
            $data = new ArrayCollection($data);
        }
        // dump($data);
        return $data;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->isEnabled() && ($data instanceof Collection || is_array($data));
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->isEnabled() ? [
                Collection::class => true,
                ArrayCollection::class => true,
                'array' => true,
            ] : ['*' => null];
    }


}