<?php
namespace Aequation\WireBundle\Dto;

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
    #[Map(if: 'count')]
    public array $content = [];
    // Associations
    public mixed $owner = null;
    public mixed $webpage = null;
    #[Map(if: 'count')]
    public Traversable|array|null $categorys = null;
    #[Map(if: 'count')]
    public Traversable|array|null $factorys = null;

}