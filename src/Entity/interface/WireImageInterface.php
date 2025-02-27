<?php
namespace Aequation\WireBundle\Entity\interface;

// Symfony
use Symfony\Component\HttpFoundation\File\File;

interface WireImageInterface extends WireItemInterface
{
    public function setFile(File $file): static;
    public function getFile(): File|null;
    public function updateName(): static;
    public function getFilename(): ?string;
    public function setFilename(?string $filename): static;
    public function getSize(): ?int;
    public function setSize(?int $size): static;
    public function setDeleteImage(bool $deleteImage): static;
    public function isDeleteImage(): bool;
}