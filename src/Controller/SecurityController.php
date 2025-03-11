<?php
namespace Aequation\WireBundle\Controller;

use Aequation\WireBundle\Form\UserDeleteType;
use Aequation\WireBundle\Form\UserType;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
// PHP
use Exception;

class SecurityController extends AbstractController
{
    // #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@AequationWire/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    // #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/profile', name: 'app_account')]
    public function profile(): Response
    {
        return $this->render('@AequationWire/security/profile.html.twig');
    }

    #[Route('/profile/delete', name: 'app_profile_delete')]
    public function delete(
        Request $request,
        Security $security,
        EntityManagerInterface $entityManager
    )
    {
        /** @var User */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(UserDeleteType::class, null, [
            'user_id' => $user->getId(),
            /** @see https://symfony.com/doc/current/security/csrf.html */
            'csrf_token_id' => 'delete_profile_token_'.$user->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data['password'] = $form->get('password')->getData();
            $data['user_id'] = intval($form->get('user_id')->getData());
            if($user->getId() !== $data['user_id']) {
                throw new Exception('Invalid user id');
            }
            // logout user
            $response = $security->logout(false);
            // remove user
            $entityManager->remove($user);
            $entityManager->flush();
            return $response;
        }

        // if($form->isSubmitted()) {
        //     $form->addError(new FormError('-- Invalid form --'));
        //     dump($form, $form->getErrors(true, true));
        // }
        return $this->render('@AequationWire/security/delete_profile.html.twig', [
            'deleteForm' => $form,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var User */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_account');
        }

        return $this->render('@AequationWire/security/edit_profile.html.twig', [
            'userForm' => $form,
        ]);
    }

}
