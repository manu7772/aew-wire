<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
// Symfony
use Symfony\Bundle\FrameworkBundle\Routing\Attribute\AsRoutingConditionService;
use Symfony\Component\HttpFoundation\Request;

/** @see https://symfony.com/doc/current/routing.html#matching-expressions */
#[AsRoutingConditionService(alias: 'wire_route_checker')]
class WireRouteChecker
{

    use TraitBaseService;

    // public function __construct(
    //     protected AppWireServiceInterface $appWire
    // )
    // {
        
    // }

    public function check(Request $request): bool
    {
        return true;
    }

}