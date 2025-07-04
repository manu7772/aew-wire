<?php
namespace Aequation\WireBundle\Interface;

use ReflectionClass;

interface ClassDescriptionInterface
{
    // Reflection class
    public static function getReflectionClass(): ReflectionClass;
    // Classname
    public function getClassname(): string;
    // Shortname
    public function getShortname(bool $lowercase = false): string;
    // Trans domain
    public function getTrans_domain(): string;

}