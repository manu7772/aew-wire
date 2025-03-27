<?php
namespace Aequation\WireBundle\Serializer;

use Aequation\WireBundle\Entity\interface\BetweenManyInterface;
// Symfony
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class BetweenManyDenormalizer implements DenormalizerInterface
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
        dd($data);
        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }


    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        $supports = $this->isEnabled()
            && is_a($type, BetweenManyInterface::class, true)
            ;
        dd($data, $supports);
        return $supports;
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->isEnabled() ? [BetweenManyInterface::class => true] : [];
    }


}