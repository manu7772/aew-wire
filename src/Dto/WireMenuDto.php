<?php
namespace Aequation\WireBundle\Dto;

// Symfony

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\ObjectMapper\Attribute\Map;
// PHP
use Traversable;

class WireMenuDto extends WireItemDto
{
    // Fields
    #[Map(if: 'strlen')]
    public ?string $title = null;
    #[Map(if: 'strlen')]
    public string $linktitle;
    #[Map(if: 'is_bool')]
    public bool $prefered = false;
    #[Map(if: 'count')]
    public array $content = [];
    // Associations
    #[Map(if: 'is_object')]
    public mixed $webpage = null;
    #[Map(if: 'is_object')]
    public ?object $language = null;
    // #[Map(if: 'count')]
    public ArrayCollection $childs;

    public function __construct(
        array $data,
        public readonly WireEntityManagerInterface $_wireEm,
        public array $_base_options = []
    )
    {
        $this->childs = new ArrayCollection();
        parent::__construct($data, $_wireEm, $_base_options);
    }

}