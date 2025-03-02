<?php
namespace Aequation\WireBundle\Entity\interface;

interface WireCategoryInterface extends WireEntityInterface, TraitDatetimedInterface, TranslationEntityInterface, SluggableInterface, TraitUnamedInterface
{

    public const DEFAULT_TYPE = 'default';

    // name
    public function getName(): string;
    public function setName(string $name): static;
    // type
    public function getType(): string;
    public function setType(string $type): static;

}