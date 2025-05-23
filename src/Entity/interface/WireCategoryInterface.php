<?php
namespace Aequation\WireBundle\Entity\interface;

interface WireCategoryInterface extends WireEntityInterface, TranslationEntityInterface, SluggableInterface, TraitUnamedInterface
{

    // name
    public function getName(): string;
    public function setName(string $name): static;
    // type
    public function getType(): ?string;
    public function setType(string $type): static;

}