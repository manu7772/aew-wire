<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Entity\interface\WireLanguageInterface;
use Aequation\WireBundle\Form\LanguageType;
use Aequation\WireBundle\Service\interface\WireLanguageServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/language', name: 'admin_language_')]
#[IsGranted("ROLE_COLLABORATOR")]
final class LanguageController extends AbstractController
{

    public function __construct(
        protected WireLanguageServiceInterface $languageService,
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
        // $this->denyAccessUnlessGranted('index', 'Language', $this->translator->trans('access_denied'));
        return $this->render('@AequationWire/admin/language/index.html.twig', $this->languageService->getPaginatedContextData($request));
    }

    // #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    // public function new(
    //     Request $request
    // ): Response
    // {
    //     $this->denyAccessUnlessGranted('new', 'Language', $this->translator->trans('access_denied'));
    //     $language = $this->languageService->createEntity();
    //     $form = $this->createForm(LanguageType::class, $language);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $this->entityManager->persist($language);
    //         $this->entityManager->flush();
    //         return $this->redirectToRoute('index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('@AequationWire/admin/language/new.html.twig', [
    //         'language' => $language,
    //         'form' => $form,
    //     ]);
    // }

    // #[Route('/{id}', name: 'show', methods: ['GET'])]
    // public function show(
    //     WireLanguageInterface $language
    // ): Response
    // {
    //     $this->denyAccessUnlessGranted('show', $language, $this->translator->trans('access_denied'));
    //     return $this->render('@AequationWire/admin/language/show.html.twig', [
    //         'language' => $language,
    //     ]);
    // }

    // #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    // public function edit(
    //     Request $request,
    //     WireLanguageInterface $language
    // ): Response
    // {
    //     $this->denyAccessUnlessGranted('edit', $language, $this->translator->trans('access_denied'));
    //     $form = $this->createForm(LanguageType::class, $language);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $this->entityManager->flush();
    //         return $this->redirectToRoute('index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('@AequationWire/admin/language/edit.html.twig', [
    //         'language' => $language,
    //         'form' => $form,
    //     ]);
    // }

    // #[Route('/{id}', name: 'delete', methods: ['POST'])]
    // public function delete(
    //     Request $request,
    //     WireLanguageInterface $language
    // ): Response
    // {
    //     $this->denyAccessUnlessGranted('delete', $language, $this->translator->trans('access_denied'));
    //     if ($this->isCsrfTokenValid('delete'.$language->getId(), $request->getPayload()->getString('_token'))) {
    //         $this->entityManager->remove($language);
    //         $this->entityManager->flush();
    //     }

    //     return $this->redirectToRoute('index', [], Response::HTTP_SEE_OTHER);
    // }
}
