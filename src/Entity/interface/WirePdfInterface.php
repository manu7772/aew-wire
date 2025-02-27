<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Component\interface\PdfizableInterface;
// Symfony
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

interface WirePdfInterface extends WireItemInterface, PdfizableInterface
{
    public function setFile(File $file): static;
    public function getFile(): File|null;
    public function updateName(): static;
    public function getFilename(): ?string;
    public function setFilename(?string $filename): static;
    public function getFilepathname($filter = null, array $runtimeConfig = [], $resolver = null, $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): ?string;
    public function getSize(): ?int;
    public function setSize(?int $size): static;
}