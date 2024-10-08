<?php
namespace Aequation\WireBundle\Tools\interface;

use Reflector;

interface ToolInterface extends Reflector
{

    public function __toString(): string;

}