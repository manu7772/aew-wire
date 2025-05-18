<?php
namespace Aequation\WireBundle\Controller\Generation;

// Aequation
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\UnameServiceInterface;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\UnitOfWork;
use Exception;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class GenerationController extends AbstractController
{
    public const GENERATION_MODE = 2;

    #[IsGranted("ROLE_SUPER_ADMIN")]
    #[Route('/report/{mode<\d>}', name: 'generate_report', defaults: ['mode' => GenerationController::GENERATION_MODE])]
    public function report(
        int $mode,
        NormalizerServiceInterface $normalizer
    ): Response
    {
        $mode = array_key_exists($mode, NormalizerServiceInterface::AVAILABLE_MODES) ? $mode : static::GENERATION_MODE;
        return $this->render('generate/report.html.twig', [
            'normalizer' => $normalizer,
            'class_reports' => $normalizer->getReport([], $mode),
            'available_modes' => NormalizerServiceInterface::AVAILABLE_MODES,
            'mode' => $mode,
        ]);
    }

    #[IsGranted("ROLE_SUPER_ADMIN")]
    #[Route('/report/{entity<\w+>}/{mode<\d>}', name: 'generate_report_entity', defaults: ['mode' => GenerationController::GENERATION_MODE])]
    public function reportEntity(
        string $entity,
        int $mode,
        NormalizerServiceInterface $normalizer
    ): Response
    {
        $mode = array_key_exists($mode, NormalizerServiceInterface::AVAILABLE_MODES) ? $mode : static::GENERATION_MODE;
        return $this->render('generate/report_entity.html.twig', [
            'normalizer' => $normalizer,
            'entity' => $entity,
            'class_reports' => $normalizer->getReport([$entity], $mode),
            'available_modes' => NormalizerServiceInterface::AVAILABLE_MODES,
            'mode' => $mode,
        ]);
    }

    // #[IsGranted("ROLE_SUPER_ADMIN")]
    #[Route('/generate/initialize/{redirect}', name: 'generate_initialize', defaults: ['redirect' => null])]
    public function generateIfBddEmpty(
        Request $request,
        WireEntityManagerInterface $wireEm,
        ?string $redirect = null
    ): RedirectResponse
    {
        $hydrateds = [];
        $flush = true;
        $normalizer = $wireEm->getNormaliserService();
        $data = $normalizer->getYamlData([], 0);
        $classnames = array_keys($data);
        $bddcounts = 0;
        foreach ($classnames as $classname) {
            if(in_array(Objects::getShortname($classname), ['Factory', 'User', 'Webpage'])) {
                $bddcounts += $wireEm->getEntitiesCount($classname);
            }
        }
        if(empty($bddcounts)) {
            $error = null;
            foreach ($classnames as $classname) {
                try {
                    $hydrateds[Objects::getShortname($classname)] = $normalizer->generateEntitiesFromClass($classname, true, null, $flush);
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    exit;
                }
            }
            if($error) {
                $this->addFlash('error', 'Error: '.$error);
            } else {
                $this->addFlash('success', count($classnames).' entities generated');
            }
        } else {
            $this->addFlash('info', 'The database is not empty, no generation done');
        }
        $redirect ??= $request->headers->get('referer');
        return $this->redirectToRoute($redirect ?? 'app_index');
    }

    #[IsGranted("ROLE_SUPER_ADMIN")]
    #[Route('/generate/{redirect}', name: 'generate_generate', methods: ['GET','POST'], defaults: ['redirect' => null])]
    public function generate(
        Request $request,
        NormalizerServiceInterface $normalizer,
        ?string $redirect = null
    ): Response
    {

        $hydrateds = [];
        $classes = [];
        $flush = false;

        $data = $normalizer->getYamlData([], 0);
        $classnames = array_keys($data);

        $form = $this->createFormBuilder(null, ['method' => 'POST'])
            ->add('classes', ChoiceType::class, [
                'choices' => array_combine($classnames, $classnames),
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
            ])
            ->add('flush', CheckboxType::class, ['label' => 'Enregistrer', 'required' => false, 'mapped' => false, 'data' => $flush])
            ->add('save', SubmitType::class, ['label' => 'Générer', 'attr' => ['data-turbo' => 'false']])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Form is submitted and valid
            // $data = $form->all();
            $classes = $form->get('classes')->getData();
            $flush = $form->get('flush')->getData();
            foreach ($classes as $classname) {
                $hydrateds[Objects::getShortname($classname)] = $normalizer->generateEntitiesFromClass($classname, true, null, $flush);
            }
            $this->addFlash('success', 'Entities generated');
            if(empty($redirect)) {
                $redirect = $request->headers->get('referer');
            }
            if(is_string($redirect)) {
                return $this->redirectToRoute($redirect);
            }
        } else {
            // Nothing to do
            // dd($classes, $flush, $hydrateds);
        }

        return $this->render('generate/generate.html.twig', [
            'hydrateds' => $hydrateds,
            'classnames' => $classnames,
            'form' => $form,
            // --- form results ---
            'form_classes' => $classes,
            'form_flush' => $flush,
        ]);
    }

    #[IsGranted("ROLE_SUPER_ADMIN")]
    #[Route('/entities/{class}', name: 'generate_entities', defaults: ['class' => null])]
    public function entities(
        WireEntityManagerInterface $wireEm,
        ?string $class = null
    ): Response
    {
        $allnamespaces = false;
        $onlyInstantiables = true;
        $entities = [];
        if($class && $wireEm->entityExists($class)) {
            if($class = $wireEm->getClassnameByShortname($class, $allnamespaces, $onlyInstantiables)) {
                $entities = $wireEm->getRepository($class)->findAll();
            }
        } else {
            $class = null;
        }
        return $this->render('generate/entities.html.twig', [
            'classnames' => $wireEm->getEntityNames(true, $allnamespaces, $onlyInstantiables),
            'class' => $class,
            'entities' => $entities,
        ]);
    }

    #[IsGranted("ROLE_SUPER_ADMIN")]
    #[Route('/orphanunames', name: 'generate_orphanunames')]
    public function orphanunames(
        UnameServiceInterface $service
    ): Response
    {
        return $this->render('generate/orphanunames.html.twig', [
            'unames' => $service->findOrphanUnames(),
        ]);
    }

    #[IsGranted("ROLE_SUPER_ADMIN")]
    #[Route('/orphanunames/cleanup/{euid}', name: 'generate_orphanunames_cleanup', defaults: ['euid' => null])]
    public function orphanunamesCleanup(
        UnameServiceInterface $service,
        #[MapEntity(mapping: ['euid' => 'euid'])]
        ?Uname $uname = null,
    ): Response
    {
        /** @var UnameServiceInterface */
        $results = $service->removeOrphanUnames($uname);
        return $this->render('generate/orphanunames.html.twig', [
            'unames' => $service->findOrphanUnames(),
            'results' => $results,
            'uname' => $uname,
        ]);
    }

    #[IsGranted("ROLE_SUPER_ADMIN")]
    #[Route('/uow', name: 'generate_uow')]
    public function uow(
        WireEntityManagerInterface $wireEm
    ): Response
    {
        $statenames = [
            UnitOfWork::STATE_MANAGED => 'MANAGED',
            UnitOfWork::STATE_NEW => 'NEW',
            UnitOfWork::STATE_DETACHED => 'DETACHED',
            UnitOfWork::STATE_REMOVED => 'REMOVED',
        ];
        $em = $wireEm->getEm();
        $uow = $wireEm->getUnitOfWork();
        // $factoryServie = $wireEm->getEntityService(Factory::class);

        // 03
        /** @var Factory */
        $entity_03 = $wireEm->createEntity(WireFactory::class);
        $entity_03->setName('Test Factory 3');
        // $uow->addToIdentityMap($entity_03);
        // $uow->scheduleForInsert($entity_03);
        $em->persist($entity_03);
        $em->persist($entity_03);
        $em->persist($entity_03);
        $em->persist($entity_03);
        // $uow->computeChangeSets();
        // $uow->computeChangeSets();
        $entity_03->setName('Test Factory 3-bis');
        // $em->flush();

        // 00
        /** @var Factory */
        $entity_00 = $wireEm->getRepository(WireFactory::class)->find(1);
        // $entity_00->setName($entity_00->getName().' - ID '.$entity_00->getId());
        // $entity_00->setAddresses(new ArrayCollection());
        // $em->detach($entity_00);
        $entity_00->doUpdate();
        // 01
        /** @var Factory */
        $entity_01 = $uow->createEntity(WireFactory::class, ['id' => 10, 'name' => 'Test Factory 1']);
        // 02
        /** @var Factory */
        $entity_02 = $wireEm->createEntity(WireFactory::class);
        $uow->registerManaged($entity_02, ['id' => 2], ['name' => 'Test Factory 2']);
        // 04
        /** @var Factory */
        $entity_04 = $wireEm->createEntity(WireFactory::class);
        $uow->registerManaged($entity_04, ['id' => 4], ['name' => 'Test Factory 4']);
        $uow->scheduleForDelete($entity_04);
        // 05
        /** @var Factory */
        $entity_05 = $wireEm->createEntity(WireFactory::class);

        // $uow->computeChangeSets();
        // dump($uow->getEntityChangeSet($entity_00));

        $entities = [
            [
                'commentaire' => 'Entity Factory loaded with find('.$entity_00->getId().')',
                'entity' => $entity_00,
                'state' => $statenames[$uow->getEntityState($entity_00, UnitOfWork::STATE_NEW)],
            ],
            [
                'commentaire' => 'Entity Factory created by UOW with createEntity()',
                'entity' => $entity_01,
                'state' => $statenames[$uow->getEntityState($entity_01, UnitOfWork::STATE_NEW)],
            ],
            [
                'commentaire' => 'Entity Factory created by WireEntityManager with createEntity() + registerManaged()',
                'entity' => $entity_02,
                'state' => $statenames[$uow->getEntityState($entity_02, UnitOfWork::STATE_NEW)],
            ],
            [
                'commentaire' => 'Entity Factory created by WireEntityManager with createEntity() + scheduleForInsert()',
                'entity' => $entity_03,
                'state' => $statenames[$uow->getEntityState($entity_03, UnitOfWork::STATE_NEW)],
            ],
            [
                'commentaire' => 'Entity Factory created by WireEntityManager with createEntity()  + registerManaged() + scheduleForDelete()',
                'entity' => $entity_04,
                'state' => $statenames[$uow->getEntityState($entity_04, UnitOfWork::STATE_NEW)],
            ],
            [
                'commentaire' => 'Entity Factory created by WireEntityManager with createEntity() but not declared in UnitOfWork',
                'entity' => $entity_05,
                'state' => $statenames[$uow->getEntityState($entity_05, UnitOfWork::STATE_NEW)],
            ],
        ];

        return $this->render('generate/uow.html.twig', [
            'entities' => $entities,
            'UnitOfWork' => $uow,
            'em' => $em,
        ]);
    }

}
