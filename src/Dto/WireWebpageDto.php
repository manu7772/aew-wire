<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\ObjectMapper\Attribute\Map;

class WireWebpageDto extends WireItemDto
{
    // Fields
    #[Map(if: 'strlen')]
    public ?string $title = null;
    #[Map(if: 'strlen')]
    public ?string $linktitle = null;
    #[Map(if: 'strlen')]
    public ?string $timezone = null;
    #[Map(if: 'strlen')]
    public string $twigfile;
    #[Map(if: 'is_bool')]
    public bool $prefered = false;
    #[Map(if: 'count')]
    public array $content = [];
    // Associations
    public mixed $mainmenu = null;
    #[Map(if: 'count')]
    public Collection $sections;

    public function __construct(
        array $data,
        public WireEntityManagerInterface $_wireEm
    )
    {
        parent::__construct($data, $_wireEm);
        $this->sections = new ArrayCollection();
    }

}