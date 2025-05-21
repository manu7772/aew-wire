<?php

namespace Aequation\WireBundle\Entity\interface;

// Aequation

use Aequation\WireBundle\Component\EntitySelfState;
use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
// Symfony
// use Symfony\Component\Uid\UuidV7 as Uuid;
// PHP
use Stringable;

interface WireEntityInterface extends BaseEntityInterface, TraitSerializableInterface
{
    // public const ICON = [
    //     'ux' => 'tabler:file',
    //     'fa' => 'fa-file'
    //     // Add other types and their corresponding icons here
    // ];
    // public const SERIALIZATION_PROPS = ['id'];
    // Interface of all entities
    public function getId(): mixed;
    // Serialization
    public function serialize(): ?string;
    public function unserialize(string $data): void;
    public function __serialize(): array;
    public function __unserialize(array $data): void;
    // Icon
    public static function getIcon(string $type = 'ux'): string;
}
