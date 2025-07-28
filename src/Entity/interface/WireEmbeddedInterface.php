<?php
namespace Aequation\WireBundle\Entity\interface;

use Stringable;

interface WireEmbeddedInterface extends Stringable
{
    public function toArray(): array;
    public function isEmpty(): bool;
}