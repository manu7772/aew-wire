<?php
namespace Aequation\WireBundle\Attribute;

use Aequation\WireBundle\Attribute\interface\AppAttributeMethodInterface;
// PHP
use ReflectionMethod;

abstract class BaseMethodAttribute extends BaseClassAttribute implements AppAttributeMethodInterface
{

    public readonly ReflectionMethod $method;
    public readonly int $startLine;

    public function setMethod(ReflectionMethod $method): static
    {
        $this->method = $method;
        $this->startLine = $method->getStartLine();
        return $this;
    }

    public function getMethod(): ReflectionMethod
    {
        return $this->method;
    }

    public function getMethodName(): string
    {
        return $this->method->name;
    }

    public function getStartLine(): int
    {
        return $this->startLine;
    }

    public function __serialize(): array
    {
        $parent = parent::__serialize();
        $parent['method'] = $this->method->name;
        return $parent;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->setMethod($this->class->getMethod($data['method']));
    }

}