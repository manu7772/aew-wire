<?php
namespace Aequation\WireBundle\Controller\Initializer;

use Aequation\WireBundle\Service\interface\InitializerInterface;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/ae-initializer', name: 'aequation_wire_initializer.')]
#[IsGranted("dev|ROLE_SUPER_ADMIN", null, 'forbidden_access', 403)]
class InitializerController extends AbstractController
{

    #[Route(path: '', name: 'home')]
    #[Template('@AequationWire/initializer/home.html.twig')]
    public function home(
        InitializerInterface $initializer
    ): array
    {
        $data = [
            'initializer' => $initializer,
        ];
        return $data;
    }

    #[Route(path: '/{action}', name: 'initialize')]
    // #[Template('@AequationWire/initializer/home.html.twig')]
    public function initialize(
        string $action,
        InitializerInterface $initializer
    ): RedirectResponse
    {
        $data = ['action' => $action, 'result' => $initializer->installConfig($action)];
        $this->addFlash('data', $data);
        return $this->redirectToRoute('aequation_wire_initializer.home');
    }

}