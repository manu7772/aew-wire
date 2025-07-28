<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\RouterInfoInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Tools\HttpRequest;
// Symfony
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

class RouterInfo implements RouterInfoInterface
{
    public readonly string $route;
    public readonly array $parameters;
    public readonly Router $router;
    public readonly bool $isCli;

    public function __construct(public readonly AppWireServiceInterface $appWire)
    {
        $this->isCli = HttpRequest::isCli();;
        $this->route = $this->appWire->getCurrent_route();
        $this->parameters = $this->appWire->getCurrent_route_parameters();
        $this->router = $this->appWire->get('router');
    }

    public function getAppWire(): AppWireServiceInterface
    {
        return $this->appWire;
    }


    public function getContext(): ?RequestContext
    {
        return $this->router?->getContext() ?: null;
    }

    public function getContextAsArray(): array
    {
        if($context = $this->getContext()) {
            return [
                'BaseUrl' => $context->getBaseUrl(),
                'PathInfo' => $context->getPathInfo(),
                'Method' => $context->getMethod(),
                'Host' => $context->getHost(),
                'Scheme' => $context->getScheme(),
                'HttpPort' => $context->getHttpPort(),
                'HttpsPort' => $context->getHttpsPort(),
                'QueryString' => $context->getQueryString(),
                // 'Parameters' => $context->getParameters(),
            ];
        }
        return [];
    }

    public function isCli(): bool
    {
        return $this->isCli;
    }

}