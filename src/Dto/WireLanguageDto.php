<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;

class WireLanguageDto extends BaseDto
{

    #[Map(if: 'strlen')]
    public ?string $locale = null;
    #[Map(if: 'strlen')]
    public ?string $timezone = null;
    #[Map(if: 'strlen')]
    public ?string $description = null;
    #[Map(if: 'is_int')]
    public ?int $position = null;
    #[Map(if: 'strlen')]
    public ?string $uname = null;
    #[Map(if: 'is_bool')]
    public bool $prefered = false;
    #[Map(if: 'is_bool')]
    public bool $enabled = true;

    public function __toString(): string
    {
        return $this->locale ?? parent::__toString();
    }

    public function __construct(
        protected mixed $data,
        protected WireEntityManagerInterface $_wireEm,
        protected array $_base_options = [],
    ) {
        parent::__construct($data, $_wireEm, $_base_options);
    }

}