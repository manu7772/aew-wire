<?php
namespace Aequation\WireBundle\Entity\interface;

// PHP
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;

interface WireArticleInterface extends WireItemInterface, TraitOwnerInterface, TraitRelinkableInterface, TraitWebpageableInterface, TraitCategorizedInterface
{
    public function getStart(): ?DateTimeInterface;
    public function setStart(?DateTimeInterface $start): static;
    public function getEnd(): ?DateTimeInterface;
    public function setEnd(?DateTimeInterface $end): static;
    public function isDeprecated(?DateTimeInterface $now = null): bool;
    public function getFactorys(): Collection;
    public function addFactory(WireFactoryInterface $factory): static;
    public function removeFactory(WireFactoryInterface $factory): static;
}