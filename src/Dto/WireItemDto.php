<?php
namespace Aequation\WireBundle\Dto;

use Symfony\Component\ObjectMapper\Attribute\Map;

class WireItemDto extends BaseDto
{
    #[Map(if: 'strlen')]
    public string $name;
    #[Map(if: 'strlen')]
    public string $uname;
    #[Map(if: 'is_bool')]
    public bool $enabled = true;
    #[Map(if: 'strlen')]
    public ?string $timezone = null;

    public function __toString(): string
    {
        return $this->name ?? parent::__toString();
    }

}