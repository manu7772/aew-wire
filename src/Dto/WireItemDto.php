<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;

abstract class WireItemDto extends BaseDto
{
    #[Map(if: 'strlen')]
    public ?string $name = null;
    #[Map(if: 'strlen')]
    public ?string $uname = null;
    #[Map(if: 'is_bool')]
    public bool $enabled = true;
    #[Map(if: 'strlen')]
    public ?string $timezone = null;
    #[Map(if: 'is_object')]
    public ?object $language = null;

    public function __toString(): string
    {
        return $this->name ?? parent::__toString();
    }

    public function __construct(
        protected mixed $data,
        protected WireEntityManagerInterface $_wireEm,
        protected array $_base_options = [],
    ) {
        parent::__construct($data, $_wireEm, $_base_options);
    }

}