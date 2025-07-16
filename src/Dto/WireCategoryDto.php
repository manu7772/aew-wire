<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\WireCategory;
// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;

// #[Map(target: WireCategory::class)]
class WireCategoryDto extends BaseDto
{

    public string $name;
    public string $type;
    #[Map(if: 'strlen')]
    public ?string $description = null;
    #[Map(if: 'strlen')]
    public ?string $uname = null;

}