<?php
namespace Aequation\WireBundle\Component\interface;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// Symfony
use Symfony\Component\Routing\RequestContext;

interface RouterInfoInterface
{
    public function getAppWire(): AppWireServiceInterface;
    public function getContext(): ?RequestContext;
    public function getContextAsArray(): array;
    public function isCli(): bool;
}