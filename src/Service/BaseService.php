<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\WireServiceInterface;
// PHP
use BadMethodCallException;

abstract class BaseService implements WireServiceInterface
{

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return static::class;
    }

    public function __sleep(): array
    {
        throw new BadMethodCallException(vsprintf('Cannot serialize %s', [static::class.(static::class !== __CLASS__ ? PHP_EOL.'(based on '.__CLASS__.')' : '')]));
    }

    public function __wakeup(): void
    {
        throw new BadMethodCallException(vsprintf('Cannot unserialize %s', [static::class.(static::class !== __CLASS__ ? PHP_EOL.'(based on '.__CLASS__.')' : '')]));
    }

}