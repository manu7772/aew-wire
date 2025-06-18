<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Form\UserType;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
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

    public readonly WireEntityServiceInterface $service;


    #[Route(name: 'index', methods: ['GET'])]
    public function index(
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('index', $this->getEntityShortname(), $this->translator->trans('access_denied'));
        return $this->render(
            $this->getTemplatePath('index'),
            $this->service->getPaginatedContextData($request)
        );
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('new', $this->getEntityShortname(), $this->translator->trans('access_denied'));
        $entity = $this->service->createEntity();
        $form = $this->createForm(UserType::class, $entity, ['validation_groups' => ['update']]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            return $this->redirectToRoute('admin_'.$this->getEntityShortname(true).'_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render($this->getTemplatePath('new'), [
            'entity' => $entity,
            'form' => $form,
            'trans_domain' => $entity->getShortname(),
        ]);
    }

    #[Route('/{id:entity}', name: 'show', methods: ['GET'])]
    public function show(
        #[ValueResolver('app_entity_value_resolver')]
        ?WireUserInterface $entity
    ): Response
    {
        if($entity) $this->denyAccessUnlessGranted('show', $entity, $this->translator->trans('access_denied'));
        return $this->render($this->getTemplatePath('show'), [
            'entity' => $entity,
            'trans_domain' => $entity?->getShortname() ?: $this->getEntityShortname(),
        ]);
    }

    #[Route('/{id:entity}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('app_entity_value_resolver')]
        ?WireUserInterface $entity
    ): Response
    {
        $this->denyAccessUnlessGranted('edit', $entity, $this->translator->trans('access_denied'));
        $form = $this->createForm(UserType::class, $entity, ['validation_groups' => ['update']]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('admin_'.$this->getEntityShortname(true).'_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render($this->getTemplatePath('edit'), [
            'entity' => $entity,
            'form' => $form,
            'trans_domain' => $entity?->getShortname() ?: $this->getEntityShortname(),
        ]);
    }

    #[Route('/{id:entity}', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('app_entity_value_resolver')]
        ?WireUserInterface $entity
    ): Response
    {
        $this->denyAccessUnlessGranted('delete', $entity, $this->translator->trans('access_denied'));
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        } else {
            $this->addFlash('error', $this->translator->trans('csrf_token_invalid'));
        }
        return $this->redirectToRoute('admin_'.$this->getEntityShortname(true).'_index', [], Response::HTTP_SEE_OTHER);
    }
}
