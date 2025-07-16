<?php
namespace Aequation\WireBundle\Dto;

// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;

class WireFactoryDto extends WireItemDto
{

    #[Map(if: 'strlen')]
    public ?string $description = null;
    #[Map(if: 'is_bool')]
    public bool $annuaire;

}