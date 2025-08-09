<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\interface\TextContentsInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\ObjectMapper\Attribute\Map;
// PHP
use Traversable;

abstract class WireEcollectionDto extends WireItemDto
{
    // Associations
    // #[Map(if: 'count')]
    public Collection $childs;

    public function __construct(
        protected mixed $data,
        protected WireEntityManagerInterface $_wireEm,
        protected array $_base_options = [],    )
    {
        $this->childs = new ArrayCollection();
        parent::__construct($data, $_wireEm, $_base_options);
    }

}