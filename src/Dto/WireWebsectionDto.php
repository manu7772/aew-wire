<?php
namespace Aequation\WireBundle\Dto;

use Symfony\Component\ObjectMapper\Attribute\Map;

class WireWebsectionDto extends WireItemDto
{
    // Fields
    #[Map(if: 'strlen')]
    public ?string $title = null;
    #[Map(if: 'strlen')]
    public string $twigfile;
    #[Map(if: 'is_bool')]
    public bool $prefered = false;
    #[Map(if: 'count')]
    public array $content = [];
    #[Map(if: 'strlen')]
    public string $sectiontype;
    // Associations
    public mixed $mainmenu = null;

}