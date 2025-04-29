<?php
namespace Aequation\WireBundle\Serializer;

use Aequation\WireBundle\Component\interface\EntityContainerInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class EntityDenormalizer implements DenormalizerInterface
{

    public const ENABLED = true;

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
        /** @var EntityContainerInterface $data */
        return $data->getEntityDenormalized($format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        $supports = $this->isEnabled()
            && $data instanceof EntityContainerInterface
            // && is_a($type, BaseEntityInterface::class, true)
            // && !is_a($type, UnameInterface::class, true)
            ;
        return $supports;
    }

    /**
     * @see https://symfony.com/doc/current/serializer/custom_normalizer.html#performance-of-normalizers-denormalizers
     * Example of supported types values:
     * 
     * return [
     *     'object' => null,             // Doesn't support any classes or interfaces
     *     '*' => false,                 // Supports any other types, but the result is not cacheable
     *     MyCustomClass::class => true, // Supports MyCustomClass and result is cacheable
     * ];
     */
    public function getSupportedTypes(?string $format): array
    {
        return $this->isEnabled() ? [
            BaseEntityInterface::class => true,
            // BetweenManyInterface::class => true,
            // TranslationEntityInterface::class => true,
        ] : ['*' => null];
    }


}
