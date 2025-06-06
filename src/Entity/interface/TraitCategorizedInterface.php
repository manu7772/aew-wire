<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;

interface TraitCategorizedInterface
{
    public function getCategorys(): Collection;
    public function setCategorys(Collection $categorys): static;
    public function addCategory(WireCategoryInterface $category): static;
    public function removeCategory(WireCategoryInterface $category): static;
    public function hasCategory(WireCategoryInterface $category): bool;
    public function searchCategory(string $name, bool $multipleResults = false): null|array|WireCategoryInterface;
}
