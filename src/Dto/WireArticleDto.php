<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\interface\TextContentsInterface;
use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;
// PHP
use Traversable;

class WireArticleDto extends WireItemDto
{
    // Fields
    #[Map(if: 'strlen')]
    public ?string $title = null;
    #[Map(if: 'strlen')]
    public ?string $linktitle = null;
    // Associations
    public mixed $owner = null;
    public mixed $webpage = null;
    #[Map(if: 'count')]
    public Traversable|array|null $categorys = null;
    #[Map(if: 'count')]
    public Traversable|array|null $factorys = null;
    #[Map(if: 'count')]
    public TextContentsInterface $content;

    public function __construct(
        public mixed $data,
        public readonly WireEntityManagerInterface $_wireEm,
        public array $_base_options = [],
    ) {
        $this->content = new TextContents();
        $this->initialize();
    }

}