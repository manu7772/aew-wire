<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\interface\TextContentsInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\TextContents;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use DateTimeImmutable;
use Symfony\Component\ObjectMapper\Attribute\Map;
// PHP
use Traversable;

class WireUserDto extends WireItemDto
{

    #[Map(if: 'strlen')]
    public ?string $firstname = null;
    public string $email;
    #[Map(if: 'strlen')]
    public ?string $description = null;
    #[Map(if: 'strlen')]
    public string $plainPassword;
    #[Map(if: 'count')]
    public Traversable|array|null $roles = [];
    #[Map(if: 'count')]
    public Traversable|array|null $cssthemes = [];
    #[Map(if: 'count')]
    public TextContentsInterface $content;
    // Calls
    #[Map(if: 'is_bool')]
    public bool $superadmin = false;

    public function __construct(
        public mixed $data,
        public readonly WireEntityManagerInterface $_wireEm,
        public array $_base_options = [],
    ) {
        $this->content = new TextContents();
        $this->initialize();
        if ($this->data instanceof WireUserInterface) {
            $this->superadmin = $this->data->isSuperadmin();
        }
    }

    public static function transformFromDto(mixed $value, mixed $source): mixed
    {
        parent::transformFromDto($value, $source);
        if($value instanceof WireUserInterface) {
            if($source->superadmin) {
                $value->setSuperadmin();
            }
            // $value->setExpiresAt(new DateTimeImmutable('now + 1 month'));
        }
        return $value;
    }

    public static function transformToDto(mixed $value, mixed $source): mixed
    {
        parent::transformToDto($value, $source);
        $source->superadmin = $value->isSuperadmin();
        return $source;
    }

}