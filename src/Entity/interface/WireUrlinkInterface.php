<?php
namespace Aequation\WireBundle\Entity\interface;


interface WireUrlinkInterface extends WireRelinkInterface
{
    public function getALink(?int $referenceType = null): ?string;
    public function getUrl(): ?string;
    public function setUrl(?string $url): static;
    public function setRoute(?string $route): static;
    public function getRoute(): string;
}