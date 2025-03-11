<?php
namespace Aequation\WireBundle\Controller;

use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Form\RegistrationFormType;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{

    // #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        // UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager,
        WireUserServiceInterface $userService,
        TranslatorInterface $translator
    ): Response
    {
        $this->denyAccessUnlessGranted('new', 'User', $translator->trans('access_denied'));
        /** @var WireUser */
        $user = $userService->createEntity();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // !!!--> password hashing moved to UserListener --!!!
            /** @var string $plainPassword */
            // $plainPassword = $form->get('plainPassword')->getData();
            // encode the plain password
            // $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email
            return $security->login($user, 'form_login', null);
        }

        return $this->render('@AequationWire/registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
