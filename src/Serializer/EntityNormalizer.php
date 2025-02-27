<?php
namespace Aequation\WireBundle\Serializer;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
// symfony
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ToOneAssociationMapping;
use Doctrine\ORM\Mapping\ToManyAssociationMapping;
// PHP
use Exception;

class EntityNormalizer implements NormalizerInterface
{

    public const ENABLED = false;

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public static function isEnabled(): bool
    {
        return static::ENABLED;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $cmd = $this->entityManager->getClassMetadata($object::class);
        // Do something here...
        $data = $this->normalizer->normalize($object, $format, $context);
        // Or do something here...
        foreach ($cmd->getAssociationMappings() as $field => $relation) {
            if(array_key_exists($field, $data)) {
                switch (true) {
                    case $relation instanceof ToOneAssociationMapping:
                        $data[$field] = $data[$field]['id'];
                        break;
                    case $relation instanceof ToManyAssociationMapping:
                        $datas = [];
                        foreach ($data[$field] as $d) {
                            $datas[] = $d['id'];
                        }
                        break;
                    default:
                        throw new Exception(vsprintf('Error %s line %d: %s not supported yet!', [__METHOD__, __LINE__, get_class($relation)]));
                        break;
                }
            }
        }
        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return static::isEnabled() ? $data instanceof WireEntityInterface : false;
    }

    public function getSupportedTypes(?string $format): array
    {
        return static::isEnabled() ? [WireEntityInterface::class => true] : [];
    }

}