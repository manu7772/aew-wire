<?php
namespace Aequation\WireBundle\Repository\interface;

interface WireWebpageRepositoryInterface extends WireItemRepositoryInterface
{
    public function findExposables(bool $onlyActive = false): array;
    public function findExposablesFor(string|object $item, bool $onlyActive = false): array;
}