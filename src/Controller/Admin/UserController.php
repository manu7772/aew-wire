<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Form\UserType;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/user', name: 'admin_user_')]
#[IsGranted("ROLE_COLLABORATOR")]
final class UserController extends AbstractController
{

    public function __construct(
        protected WireUserServiceInterface $userService,
        protected EntityManagerInterface $entityManager,
        protected TranslatorInterface $translator
    )
    {
        // dump($this->getSubscribedServices());
    }

    #[Route(name: 'index', methods: ['GET'])]
    public function index(
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('index', 'User', $this->translator->trans('access_denied'));
        return $this->render('@AequationWire/admin/user/index.html.twig', $this->userService->getPaginatedContextData($request));
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('new', 'User', $this->translator->trans('access_denied'));
        $user = $this->userService->createEntity();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return $this->redirectToRoute('index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('@AequationWire/admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping:['id' => 'id'])]
        WireUserInterface $user
    ): Response
    {
        $this->denyAccessUnlessGranted('show', $user, $this->translator->trans('access_denied'));
        return $this->render('@AequationWire/admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(mapping:['id' => 'id'])]
        WireUserInterface $user
    ): Response
    {
        $this->denyAccessUnlessGranted('edit', $user, $this->translator->trans('access_denied'));
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('@AequationWire/admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(mapping:['id' => 'id'])]
        WireUserInterface $user
    ): Response
    {
        $this->denyAccessUnlessGranted('delete', $user, $this->translator->trans('access_denied'));
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('index', [], Response::HTTP_SEE_OTHER);
    }
}
