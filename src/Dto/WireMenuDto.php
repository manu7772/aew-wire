<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\interface\TextContentsInterface;
use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;
// PHP
use Traversable;

class WireMenuDto extends WireEcollectionDto
{
    // Fields
    #[Map(if: 'strlen')]
    public ?string $title = null;
    #[Map(if: 'strlen')]
    public string $linktitle;
    #[Map(if: 'is_bool')]
    public bool $prefered = false;
    #[Map(if: 'count')]
    public TextContentsInterface $content;
    // Associations
    #[Map(if: 'is_object')]
    public mixed $webpage = null;
    #[Map(if: 'is_object')]
    public ?object $language = null;

    public function __construct(
        protected mixed $data,
        protected WireEntityManagerInterface $_wireEm,
        protected array $_base_options = [],
    )
    {
        $this->content = new TextContents();
        parent::__construct($data, $_wireEm, $_base_options);
    }

}