<?php
namespace Aequation\WireBundle\Entity\interface;


interface WireTranslationInterface extends BaseEntityInterface
{
    public const ICON = [
        'ux' => 'tabler:flag',
        'fa' => 'fa-flag'
        // Add other types and their corresponding icons here
    ];
    public const SERIALIZATION_PROPS = ['id','locale','field','content'];
    public const DO_EMBED_STATUS_EVENTS = [];

    public function __construct($locale, $field, $value);
    // public function __construct_translation(): void;
    // Serialization
    public function serialize(): ?string;
    public function unserialize(string $data): void;
    public function __serialize(): array;
    public function __unserialize(array $data): void;
    // Icon
    public static function getIcon(string $type = 'ux'): string;
    // Euid
    public function getEuid(): string;
    public function setEuid(string $euid): static;
    public function getUnameThenEuid(): string;

    public function getId();
    public function setLocale($locale);
    public function getLocale();
    public function setField($field);
    public function getField();
    public function setObject($object);
    public function getObject();
    public function setContent($content);
    public function getContent();

}