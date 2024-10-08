<?php
namespace Aequation\WireBundle\Service\interface;


interface WireCategoryServiceInterface extends WireEntityServiceInterface
{

    public function getCategoryTypeChoices(): array;

}