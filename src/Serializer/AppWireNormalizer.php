<?php
namespace Aequation\WireBundle\Serializer;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Service\AppWireService;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// symfony
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ToOneAssociationMapping;
use Doctrine\ORM\Mapping\ToManyAssociationMapping;
// PHP
use Exception;

class AppWireNormalizer implements NormalizerInterface
{

    public const ENABLED = true;

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer
    ) {}

    public function isEnabled(): bool
    {
        return static::ENABLED;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        /** @var AppWireServiceInterface $object */
        // Do something here...
        // dump($object);
        // if($object->isDev() && empty($object->getUser())) {
        //     $sadmin = $object->getUserService()->getMainSAdminUser();
        //     $object->getUserService()->loginUser($sadmin);
        //     dump($sadmin, $object->getUser());
        // }
        $data = $this->normalizer->normalize($object, $format, $context);
        // Or do something here...
        // dd($data);
        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->isEnabled() ? $data instanceof AppWireServiceInterface : false;
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->isEnabled() ? [
            AppWireServiceInterface::class => true,
            // AppWireService::class => true,
        ] : [];
    }

}