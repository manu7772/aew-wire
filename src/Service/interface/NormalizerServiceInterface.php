<?php
namespace Aequation\WireBundle\Service\interface;

// PHP

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use ArrayObject;

interface NormalizerServiceInterface extends WireServiceInterface
{
    // Normalize
    public function normalize(mixed $data, ?string $format = null, ?array $context = [], ?bool $convertToArrayList = false): array|string|int|float|bool|ArrayObject|null;
    public function denormalize(mixed $data, string $type, ?string $format = null, ?array $context = []): mixed;
    // Normalize entity
    public function normalizeEntity(WireEntityInterface $entity, ?string $format = null, ?array $context = []): array|string|int|float|bool|ArrayObject|null;
    public function denormalizeEntity(mixed $data, string $type, ?string $format = null, ?array $context = []): WireEntityInterface;
    // Serialize
    public function serialize(mixed $data, string $format, ?array $context = [], ?bool $convertToArrayList = false): string;
    public function deserialize(string $data, string $type, string $format, ?array $context = []): mixed;
}