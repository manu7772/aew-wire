<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfonycasts\TailwindBundle\TailwindBuilder;

#[Route('/admin', name: 'admin_')]
#[IsGranted("ROLE_COLLABORATOR")]
class DashboardController extends AbstractController
{

    #[Route(name: 'dashboard', alias: ['index','home'])]
    public function index(
        #[Autowire(service: 'tailwind.builder')]
        TailwindBuilder $tailwindBuilder,
    ): Response
    {
        $this->addFlash('success', 'Welcome to the admin dashboard!');
        return $this->render('@AequationWire/admin/dashboard/index.html.twig', [
            'tailwindBuilder' => $tailwindBuilder,
        ]);
    }

    #[Route('/help', name: 'help')]
    public function help(): Response
    {
        $this->addFlash('success', 'Page aide en ligne');
        return $this->render('@AequationWire/admin/dashboard/help.html.twig');
    }

}
