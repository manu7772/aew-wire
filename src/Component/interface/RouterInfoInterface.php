<?php
namespace Aequation\WireBundle\Component\interface;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// Symfony
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

interface RouterInfoInterface
{
    public function getAppWire(): AppWireServiceInterface;
    public function getRouter(): Router;
    public function getContext(): ?RequestContext;
    public function getContextAsArray(): array;
    public function isCli(): bool;
}