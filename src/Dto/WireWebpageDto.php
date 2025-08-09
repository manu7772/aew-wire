<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\interface\WpexposeCollectionInterface;
use Aequation\WireBundle\Entity\interface\TextContentsInterface;
use Aequation\WireBundle\Entity\interface\TwigfileInterface;
use Aequation\WireBundle\Entity\Twigfile;
use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Entity\WpexposeCollection;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Files;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    public null|string|TwigfileInterface $twigfile = null;
    #[Map(if: 'is_bool')]
    public bool $prefered = false;
    #[Map(if: 'count')]
    public null|array|TextContentsInterface $content;
    #[Map(if: 'count')]
    public null|array|WpexposeCollectionInterface $wpexposes;
    // Associations
    public mixed $mainmenu = null;
    #[Map(if: 'count')]
    public Collection $sections;

    public function __construct(
        protected mixed $data,
        protected WireEntityManagerInterface $_wireEm,
        protected array $_base_options = [],    )
    {
        $this->sections = new ArrayCollection();
        $this->twigfile = new Twigfile();
        $this->content = new TextContents();
        $this->wpexposes = new WpexposeCollection();
        parent::__construct($data, $_wireEm, $_base_options);
    }

}