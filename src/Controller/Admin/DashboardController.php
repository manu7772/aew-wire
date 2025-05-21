<?php
namespace Aequation\WireBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted("ROLE_COLLABORATOR")]
class DashboardController extends AbstractController
{

    #[Route(name: 'index')]
    public function index(): Response
    {
        $this->addFlash('success', 'Welcome to the admin dashboard!');
        return $this->render('@AequationWire/admin/dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

}
