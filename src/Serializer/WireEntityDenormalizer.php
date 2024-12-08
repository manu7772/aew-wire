<?php
namespace Aequation\WireBundle\Serializer;

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class WireEntityDenormalizer implements DenormalizerInterface
{

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly DenormalizerInterface $denormalizer,
        private readonly WireEntityManagerInterface $entityManager
    ) {}

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if(isset($context['wire_hidrate']) && true === (bool)$context['wire_hidrate']) {
            dd($context, $data);

        }
        // Do something here...
        $entity = $this->denormalizer->denormalize($data, $type, $format, $context);
        // Or do something here...
        return $entity;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return
            is_a($type, WireEntityInterface::class, true)
            // && isset($data['classname'])
            ;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            WireEntityInterface::class => true,
        ];
    }

}