<?php
namespace Aequation\WireBundle\Controller;

// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/ae-initializer', name: 'aequation_wire_initializer_.')]
#[IsGranted("dev|ROLE_SUPER_ADMIN", null, 'forbidden_access', 403)]
class InitializerController extends AbstractController
{

    #[Route(path: '', name: 'home')]
    #[Template('@AequationWire/initializer/home.html.twig')]
    public function home(): array
    {
        $data = [];
        return $data;
    }

}