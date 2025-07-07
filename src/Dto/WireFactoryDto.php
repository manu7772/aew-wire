<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Dto\interfaace\WireEntityDtoInterface;
use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;
// PHP
use stdClass;

#[Map(target: WireFactory::class)]
class WireFactoryDto implements WireEntityDtoInterface
{

    // public ?int $id = null;
    public ?string $name = null;
    public ?string $description = null;
    // #[Map(target: 'uname')]
    public ?string $uname = null;
    public ?bool $annuaire = null;

    public function __construct(
        array|string|stdClass $data,
        array $context = []
    ) {
        if(!($data instanceof stdClass)) {
            $data = Objects::toStdClass($data); // Convert array or Json string to stdClass
        }
        foreach ($data as $attr => $value) {
            if(property_exists($this, $attr)) {
                $this->{$attr} = $value;
            }
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }

}