<?php
namespace Aequation\WireBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfonycasts\TailwindBundle\TailwindBuilder;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;

#[Route('/help', name: 'help_')]
// #[IsGranted("ROLE_COLLABORATOR")]
class HelpController extends AbstractController
{

    #[Route('', name: 'index')]
    public function help(): Response
    {
        // $this->addFlash('success', 'Page aide en ligne');
        return $this->render('@AequationWire/admin/dashboard/help.html.twig');
    }

    #[Route('/check-sadmin', name: 'check_sadmin')]
    public function checkSadminUser(
        Request $request,
        WireUserServiceInterface $userService,
    ): Response
    {
        $exists = $userService->getMainSAdminUser(false);
        if($exists instanceof WireUserInterface) {
            $this->addFlash('info', 'Le super administrateur existe déjà : '.$exists->getUserIdentifier());
        } else {
            $sadmin = $userService->checkMainSuperadmin();
            if(!$sadmin instanceof WireUserInterface) {
                $this->addFlash('error', 'Aucun super admin user trouvé et impossible d\'en créer un !');
            } else {
                $this->addFlash('success', sprintf('Un nouveau super admin (enabled: %s / is superadmin: %s) utilisateur a été créé: %s (%s)', $sadmin->isEnabled() ? 'yes' : 'no', $sadmin->isSuperadmin() ? 'yes' : 'no', $sadmin->getUserIdentifier(), implode(', ', $sadmin->getRoles())));
            }
        }
        
        // Try redirect to the previous page
        if($referer = $request->headers->get('referer')) {
            return $this->redirect($referer);
        }
        // Fallback to user index page
        return $this->redirectToRoute('admin_user_index');
    }

}
