<?php
namespace Aequation\WireBundle\Entity\interface;

interface TwigfileInterface extends WireEmbeddedInterface
{
    public function __construct(?string $path = null);
    public function setPath(?string $path): static;
    public function getPath(): ?string;
    public function getName(): ?string;
}