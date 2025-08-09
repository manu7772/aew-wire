<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\WireCategory;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;

// #[Map(target: WireCategory::class)]
class WireCategoryDto extends BaseDto
{

    #[Map(if: 'strlen')]
    public ?string $name = null;
    #[Map(if: 'strlen')]
    public ?string $type = null;
    #[Map(if: 'strlen')]
    public ?string $description = null;
    #[Map(if: 'strlen')]
    public ?string $uname = null;

    public function __construct(
        protected mixed $data,
        protected WireEntityManagerInterface $_wireEm,
        protected array $_base_options = [],
    ) {
        parent::__construct($data, $_wireEm, $_base_options);
    }

}