<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Form\WireUserType;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
// PHP
use RuntimeException;

#[Route('/admin/user', name: 'admin_user_')]
#[IsGranted("ROLE_COLLABORATOR")]
class UserController extends EntityController
{

    public const ENTITY_CLASS = WireUserInterface::class;
    // public const FORM_CLASS = WireUserType::class;

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
