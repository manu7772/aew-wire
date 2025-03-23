<?php
namespace Aequation\WireBundle\Service\interface;


interface WireCategoryServiceInterface extends WireEntityServiceInterface
{

    public function getAvailableTypes(bool $asShornames = true): array;
    public function getCategoryTypeChoices(): array;

}