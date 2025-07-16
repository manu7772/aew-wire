<?php
namespace Aequation\WireBundle\Dto;


class WireLanguageDto extends BaseDto
{

    public ?string $locale = null;
    public ?string $timezone = null;
    public ?string $description = null;
    public ?int $position = null;
    public ?string $uname = null;
    public bool $prefered = false;
    public bool $enabled = true;

    public function __toString(): string
    {
        return $this->locale ?? parent::__toString();
    }

}