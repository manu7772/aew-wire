<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\interface\TextContentsInterface;
use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;
// PHP
use Traversable;

class WireFactoryDto extends WireItemDto
{

    #[Map(if: 'strlen')]
    public ?string $description = null;
    #[Map(if: 'count')]
    public Collection $associates;
    #[Map(if: 'count')]
    public TextContentsInterface $content;

    public function __construct(
        protected mixed $data,
        protected WireEntityManagerInterface $_wireEm,
        protected array $_base_options = [],
    ) {
        $this->associates = new ArrayCollection();
        $this->content = new TextContents();
        parent::__construct($data, $_wireEm, $_base_options);
    }

}