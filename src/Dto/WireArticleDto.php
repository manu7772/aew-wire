<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\interface\TextContentsInterface;
use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    public Collection $categorys;
    #[Map(if: 'count')]
    public Collection $factorys;
    #[Map(if: 'count')]
    public TextContentsInterface $content;

    public function __construct(
        protected mixed $data,
        protected WireEntityManagerInterface $_wireEm,
        protected array $_base_options = [],
    ) {
        $this->categorys = new ArrayCollection();
        $this->factorys = new ArrayCollection();
        $this->content = new TextContents();
        parent::__construct($data, $_wireEm, $_base_options);
    }

}