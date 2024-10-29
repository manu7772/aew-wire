<?php
namespace Aequation\WireBundle\Serializer;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
// symfony
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class WireEntityNormalizer implements NormalizerInterface
{

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
        protected UploaderHelper $vichHelper,
        protected CacheManager $liipCache,
    ) {}

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        // Do something here...
        $data = $this->normalizer->normalize($object, $format, $context);
        // Or do something here...
        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof WireEntityInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            WireEntityInterface::class => true,
        ];
    }

}