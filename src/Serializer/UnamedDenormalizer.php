<?php
namespace Aequation\WireBundle\Serializer;

use Aequation\WireBundle\Component\NormalizeDataContainer;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Encoders;
use Exception;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Stringable;

class UnamedDenormalizer implements DenormalizerInterface
{

    public const ENABLED = false;

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
        $entity = $this->wireEm->findEntityByUname($data);
        if(empty($entity)) {
            throw new Exception(vsprintf('Error %s line %d: Entity with uname %s not found!', [__METHOD__, __LINE__, $data]));
        }
        return $entity;
    }


    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->isEnabled() && Encoders::isUnameFormatValid($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->isEnabled() ? [Stringable::class => true] : ['*' => null];
    }


}