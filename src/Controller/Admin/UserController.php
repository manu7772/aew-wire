<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Form\UserType;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
class UserController extends AbstractController
{
    public const ENTITY_CLASS = WireUserInterface::class;

    public readonly WireEntityServiceInterface $service;

    public function __construct(
        protected WireEntityManagerInterface $wireEm,
        protected EntityManagerInterface $entityManager,
        protected TranslatorInterface $translator
    )
    {
        $this->service = $this->wireEm->getEntityService(static::ENTITY_CLASS);
        if (!is_a($this->service->getEntityClassname(), static::ENTITY_CLASS, true)) {
            throw new RuntimeException(vsprintf('Error %s line %d: Service found does not manage an instance of "%s".', [__METHOD__, __LINE__, static::ENTITY_CLASS]));
        }
    }

    public function getEntityClassname(): string
    {
        return $this->service->getEntityClassname();
    }

    public function getEntityShortname(
        bool $lowercase = false
    ): string
    {
        $shortname = $this->service->getEntityShortname();
        return $lowercase ? strtolower($shortname) : $shortname;
    }

    #[Route(name: 'index', methods: ['GET'])]
    public function index(
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('index', $this->getEntityShortname(), $this->translator->trans('access_denied'));
        return $this->render(
            '@AequationWire/admin/'.$this->getEntityShortname(true).'/index.html.twig',
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
        $form = $this->createForm(UserType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            return $this->redirectToRoute('admin_'.$this->getEntityShortname(true).'_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('@AequationWire/admin/'.$this->getEntityShortname(true).'/new.html.twig', [
            $this->getEntityShortname(true) => $entity,
            'form' => $form,
            'trans_domain' => $entity->getShortname(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        #[ValueResolver('app_entity_value_resolver')]
        ?WireUserInterface $entity
    ): Response
    {
        if($entity) $this->denyAccessUnlessGranted('show', $entity, $this->translator->trans('access_denied'));
        return $this->render('@AequationWire/admin/'.$this->getEntityShortname(true).'/show.html.twig', [
            $this->getEntityShortname(true) => $entity,
            'trans_domain' => $entity?->getShortname() ?: $this->getEntityShortname(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('app_entity_value_resolver')]
        ?WireUserInterface $entity
    ): Response
    {
        $this->denyAccessUnlessGranted('edit', $entity, $this->translator->trans('access_denied'));
        $form = $this->createForm(UserType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('admin_'.$this->getEntityShortname(true).'_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('@AequationWire/admin/'.$this->getEntityShortname(true).'/edit.html.twig', [
            $this->getEntityShortname(true) => $entity,
            'form' => $form,
            'trans_domain' => $entity?->getShortname() ?: $this->getEntityShortname(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
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
