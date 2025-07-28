<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\interface\TextContentsInterface;
use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Entity\Twigfile;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Files;
// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

class WireWebsectionDto extends WireItemDto
{
    // Fields
    #[Map(if: 'strlen')]
    public ?string $title = null;
    #[Map(if: 'strlen')]
    #[Assert\Regex(pattern: Files::TWIGFILE_MATCH, match: true, message: 'Le format du fichier est invalide.', groups: ['persist','update'])]
    public Twigfile $twigfile;
    #[Map(if: 'is_bool')]
    public bool $prefered = false;
    #[Map(if: 'count')]
    public TextContentsInterface $content;
    #[Map(if: 'strlen')]
    public string $sectiontype;
    // Associations
    public mixed $mainmenu = null;

    public function __construct(
        public mixed $data,
        public readonly WireEntityManagerInterface $_wireEm,
        public array $_base_options = []
    )
    {
        $this->twigfile = new Twigfile();
        $this->content = new TextContents();
        $this->initialize();
    }

}