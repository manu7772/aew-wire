<?php
namespace Aequation\WireBundle\Dto;

// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;
// PHP
use Traversable;

class WireFactoryDto extends WireItemDto
{

    #[Map(if: 'strlen')]
    public ?string $description = null;
    #[Map(if: 'count')]
    public Traversable|array|null $associates = [];

}