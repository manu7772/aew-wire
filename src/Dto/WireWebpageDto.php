<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\ObjectMapper\Attribute\Map;
// PHP
use Traversable;

class WireWebpageDto extends WireItemDto
{
    // Fields
    #[Map(if: 'strlen')]
    public ?string $title = null;
    #[Map(if: 'strlen')]
    public ?string $linktitle = null;
    #[Map(if: 'strlen')]
    public string $twigfile;
    #[Map(if: 'is_bool')]
    public bool $prefered = false;
    #[Map(if: 'count')]
    public array $content = [];
    // Associations
    public null|WireMenuInterface|WireMenuDto $mainmenu = null;
    #[Map(if: 'count')]
    public Traversable $sections;

    public function __construct(
        array $data,
        public readonly WireEntityManagerInterface $_wireEm,
        public array $_base_options = []
    )
    {
        $this->sections = new ArrayCollection();
        parent::__construct($data, $_wireEm, $_base_options);
    }

}