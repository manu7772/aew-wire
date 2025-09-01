<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// PHP
use RuntimeException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

abstract class EntityController extends AbstractController
{

    public const ENTITY_CLASS = BaseEntityInterface::class;
    // public const FORM_CLASS = null;

    public readonly WireClassMetadataInterface $wCmd;
    public readonly WireEntityServiceInterface $service;

    public function __construct(
        protected WireEntityManagerInterface $wireEm,
        protected EntityManagerInterface $entityManager,
        protected TranslatorInterface $translator
    )
    {
        $wCmds = $this->wireEm->getEntitiesMetadata()->findFinals([static::ENTITY_CLASS]);
        if($wCmds->count() !== 1) {
            throw new RuntimeException(vsprintf('Error %s line %d: Unable to determine one unique entity for class "%s". Found %d entities.', [__METHOD__, __LINE__, static::ENTITY_CLASS, $wCmds->count()]));
        }
        $this->wCmd = $wCmds->first();
        $this->service = $this->wireEm->getEntityService($this->wCmd->getName());
    }

    protected function getTemplatePath(
        string $action
    ): string
    {
        $templates = [
            '@AequationWire/admin/entity/'.strtolower($this->wCmd->getShortName()).'/'.$action.'.html.twig',
            '@AequationWire/admin/entity/'.$action.'.html.twig',
        ];
        foreach ($templates as $template) {
            if($this->container->get('twig')->getLoader()->exists($template)) {
                return $template;
            }
        }
        throw new RuntimeException(vsprintf('Error %s line %d: No template found for action "%s" with entity "%s".', [__METHOD__, __LINE__, $action, $this->wCmd->getName()]));
    }

    // protected function getEntityClassname(): string
    // {
    //     return $this->service->getEntityClassname();
    // }

    // protected function getEntityShortname(
    //     bool $lowercase = false
    // ): string
    // {
    //     return $lowercase ? strtolower($this->wCmd->getShortName()) : $this->wCmd->getShortName();
    // }

    protected function getAdminRoute(
        string $action
    ): string
    {
        return 'admin_'.strtolower($this->wCmd->getShortName()).'_'.$action;
    }


    #[Route(name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('index', $this->wCmd->getShortName(), $this->translator->trans('access_denied'));
        return $this->render(
            $this->getTemplatePath('index'),
            [
                'classname' => $this->wCmd->getName(),
            ]
        );
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        // Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('new', $this->wCmd->getShortName(), $this->translator->trans('access_denied'));
        $model = $this->service->createModel();
        // $entity = $this->service->createEntity();
        // $form = $this->createForm((string) static::FORM_CLASS, $entity, ['validation_groups' => ['update']]);
        // $form->handleRequest($request);
        // if ($form->isSubmitted() && $form->isValid()) {
        //     $this->entityManager->persist($entity);
        //     $this->entityManager->flush();
        //     return $this->redirectToRoute($this->getAdminRoute('index'), [], Response::HTTP_SEE_OTHER);
        // }
        return $this->render($this->getTemplatePath('new'), [
            'model' => $model,
            'entity' => $this->wCmd->getName(),
            // 'form' => $form,
            // 'trans_domain' => $entity->getTrans_domain(),
        ]);
    }

    #[Route('/{id:entity}', name: 'show', methods: ['GET'])]
    public function show(
        #[ValueResolver('app_entity_value_resolver')]
        ?BaseEntityInterface $entity
    ): Response
    {
        if($entity) {
            $this->denyAccessUnlessGranted('show', $entity, $this->translator->trans('access_denied'));
        }
        return $this->render($this->getTemplatePath('show'), [
            'entity' => $entity,
            'trans_domain' => $entity?->getTrans_domain() ?: $this->wCmd->getShortName(),
        ]);
    }

    #[Route('/{id:entity}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        // Request $request,
        #[ValueResolver('app_entity_value_resolver')]
        ?BaseEntityInterface $entity
    ): Response
    {
        $this->denyAccessUnlessGranted('edit', $entity, $this->translator->trans('access_denied'));
        // $form = $this->createForm((string) static::FORM_CLASS, $entity, ['validation_groups' => ['update']]);
        // $form->handleRequest($request);
        // if ($form->isSubmitted() && $form->isValid()) {
        //     $this->entityManager->flush();
        //     return $this->redirectToRoute($this->getAdminRoute('index'), [], Response::HTTP_SEE_OTHER);
        // }
        return $this->render($this->getTemplatePath('edit'), [
            'entity' => $entity,
            // 'form' => $form,
            'trans_domain' => $entity?->getTrans_domain() ?: $this->wCmd->getShortName(),
        ]);
    }

    /**
     * @see https://symfony.com/doc/current/security/csrf.html#generating-and-checking-csrf-tokens-manually
     */
    #[Route('/{id:entity}', name: 'delete', methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete" ~ args["entity"].getId()'), tokenKey: '_token')]
    public function delete(
        // Request $request,
        #[ValueResolver('app_entity_value_resolver')]
        ?BaseEntityInterface $entity
    ): Response
    {
        $this->denyAccessUnlessGranted('delete', $entity, $this->translator->trans('access_denied'));
        // if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        // } else {
            // $this->addFlash('error', $this->translator->trans('csrf_token_invalid'));
        // }
        return $this->redirectToRoute($this->getAdminRoute('index'), [], Response::HTTP_SEE_OTHER);
    }

}