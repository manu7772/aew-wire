<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\interface\TextContentsInterface;
use Aequation\WireBundle\Entity\interface\TwigfileInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Entity\Twigfile;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Files;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;
// PHP
use Traversable;

class WireWebpageDto extends WireItemDto
{
    // Fields
    #[Map(if: 'strlen')]
    public ?string $title = null;
    #[Map(if: 'strlen')]
    public ?string $linktitle = null;
    #[Map(if: 'is_object')]
    #[Assert\Regex(pattern: Files::TWIGFILE_MATCH, match: true, message: 'Le format du fichier est invalide.', groups: ['persist','update'])]
    public TwigfileInterface $twigfile;
    #[Map(if: 'is_bool')]
    public bool $prefered = false;
    #[Map(if: 'count')]
    public TextContentsInterface $content;
    // Associations
    public null|WireMenuInterface|WireMenuDto $mainmenu = null;
    #[Map(if: 'count')]
    public Traversable $sections;

    public function __construct(
        public mixed $data,
        public readonly WireEntityManagerInterface $_wireEm,
        public array $_base_options = []
    )
    {
        $this->sections = new ArrayCollection();
        $this->twigfile = new Twigfile();
        $this->content = new TextContents();
        $this->initialize();
    }

}