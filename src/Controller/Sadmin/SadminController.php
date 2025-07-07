<?php
namespace Aequation\WireBundle\Controller\Sadmin;

use Aequation\WireBundle\Service\interface\DebugSadminInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sadmin', name: 'sadmin_')]
#[IsGranted("ROLE_SUPER_ADMIN")]
class SadminController extends AbstractController
{

    #[Route(path: '', name: 'dashboard')]
    public function dashboard(): Response
    {
        return $this->render('@AequationWire/sadmin/dashboard.html.twig');
    }

    #[Route(path: '/debug-to-optimize', name: 'debug_to_optimize')]
    public function debugToOptimize(
        DebugSadminInterface $debugSadmin
    ): Response
    {
        $toOptimize = $debugSadmin->getToOptimize();
        return $this->render('@AequationWire/sadmin/debug_to_optimize.html.twig', [
            'toOptimize' => $toOptimize,
        ]);
    }

} 