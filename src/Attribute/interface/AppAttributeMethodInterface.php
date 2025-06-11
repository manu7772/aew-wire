<?php
namespace Aequation\WireBundle\Attribute\interface;

// PHP
use ReflectionMethod;

interface AppAttributeMethodInterface extends AppAttributeInterface
{

    public function setMethod(ReflectionMethod $method): static;
    public function getMethod(): ReflectionMethod;
    public function getMethodName(): ?string;
    public function getStartLine(): ?int;

}
