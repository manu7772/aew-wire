<?php
namespace Aequation\WireBundle\Dto;

use Symfony\Component\ObjectMapper\Attribute\Map;

class WireItemDto extends BaseDto
{
    // public ?string $euid = null;
    // public ?string $classname = null;
    // public ?string $shortname = null;
    // public ?int $updates = null;
    // public ?int $id = null;
    #[Map(if: 'strlen')]
    public string $name;
    #[Map(if: 'strlen')]
    public string $uname;
    #[Map(if: 'is_bool')]
    public bool $enabled = true;

    public function __toString(): string
    {
        return $this->name ?? parent::__toString();
    }

}