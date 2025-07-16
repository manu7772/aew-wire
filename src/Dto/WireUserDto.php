<?php
namespace Aequation\WireBundle\Dto;

use Symfony\Component\ObjectMapper\Attribute\Map;

class WireUserDto extends WireItemDto
{

    #[Map(if: 'strlen')]
    public ?string $firstname = null;
    public string $email;
    #[Map(if: 'strlen')]
    public string $description;
    #[Map(if: 'count')]
    public array $roles = [];
    public string $plainPassword;
    #[Map(if: 'count')]
    public array $csstheme = [];
    #[Map(if: 'strlen')]
    public ?string $timezone = null;
    // Calls
    #[Map(if: 'is_bool')]
    public bool $superadmin = false;

}