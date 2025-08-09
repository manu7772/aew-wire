<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Dto\transform\TextContentsTransformer;
use Aequation\WireBundle\Entity\interface\TextContentsInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;
// PHP
use Traversable;

class WireUserDto extends WireItemDto
{

    #[Map(if: 'strlen')]
    public ?string $firstname = null;
    #[Map(if: 'strlen')]
    public ?string $email = null;
    #[Map(if: 'strlen')]
    public ?string $description = null;
    #[Map(if: 'strlen')]
    public ?string $plainPassword = null;
    #[Map(if: 'strlen')]
    public ?string $password = null;
    #[Map(if: [self::class, 'not_empty'])]
    public mixed $portrait = null;
    #[Map(if: 'count')]
    public Traversable|array|null $roles = [];
    #[Map(if: 'count')]
    public Traversable|array|null $cssthemes = [];
    #[Map(if: 'count', transform: TextContentsTransformer::class)]
    public null|array|TextContentsInterface $content;
    // Calls
    #[Map(if: 'is_bool')]
    public bool $superadmin = false;

    public function __construct(
        protected mixed $data,
        protected WireEntityManagerInterface $_wireEm,
        protected array $_base_options = [],
    ) {
        $this->content = new TextContents();
        parent::__construct($data, $_wireEm, $_base_options);
        if ($this->data instanceof WireUserInterface) {
            $this->superadmin = $this->data->isSuperadmin();
        }
    }

}