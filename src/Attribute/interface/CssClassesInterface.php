<?php
namespace Aequation\WireBundle\Attribute\interface;

// PHP
use Serializable;

interface CssClassesInterface extends Serializable
{

    public function getCssClasses(): array;

}