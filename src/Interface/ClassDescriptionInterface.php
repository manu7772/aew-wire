<?php
namespace Aequation\WireBundle\Interface;

interface ClassDescriptionInterface
{
    // Classname
    public function getClassname(): string;
    // Shortname
    public function getShortname(bool $lowercase = false): string;
    // Trans domain
    public function getTrans_domain(): string;

}