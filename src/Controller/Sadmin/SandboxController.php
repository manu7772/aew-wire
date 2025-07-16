<?php
namespace Aequation\WireBundle\Controller\Sadmin;

use Aequation\WireBundle\Dto\WireFactoryDto;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Service\WireEntityManager;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;
use Symfony\Component\Uid\UuidV8;
use Symfonycasts\TailwindBundle\AssetMapper\TailwindCssAssetCompiler;
use Symfonycasts\TailwindBundle\TailwindBuilder;

#[Route('/sadmin/sandbox', name: 'sadmin_sandbox_')]
#[IsGranted("ROLE_SUPER_ADMIN")]
class SandboxController extends AbstractController
{

    #[Route(path: '/dto', name: 'dto')]
    public function dto(
        WireEntityManager $wireEm,
        ObjectMapperInterface $objectMapper
    ): Response
    {
        $factory_data = [
            'id' => null,
            'name' => 'Test Factory',
            'description' => 'This is a test factory description.',
            'uname' => 'test_factory_001',
            'annuaire' => true
        ];
        $factory_classname = $wireEm->findOneFinal(WireFactory::class);
        $factory_dto = $wireEm->createDto($factory_classname, $factory_data);
        $factory = $wireEm->getRepository($factory_classname)->findOneBy(['prefered' => true]);
        $new_factory = $wireEm->createEntity($factory_classname);
        // $factory_result = $objectMapper->map($factory_dto, $factory);
        $factory_result = $objectMapper->map($factory_dto, $new_factory);
        return $this->render('@AequationWire/sadmin/sandbox_dto.html.twig', [
            'factory_classname' => $factory_classname,
            'factory_data' => $factory_data,
            'factory_result' => $factory_result,
        ]);
    }

    #[Route(path: '/tailwind', name: 'tailwind')]
    public function tailwind(
        // #[Autowire(service: 'tailwind.css_asset_compiler')]
        // TailwindCssAssetCompiler $tailwindCssAssetCompiler,
        #[Autowire(service: 'tailwind.builder')]
        TailwindBuilder $tailwindBuilder,
    ): Response
    {
        return $this->render('@AequationWire/sadmin/sandbox_tailwind.html.twig', [
            'tailwindBuilder' => $tailwindBuilder,
        ]);
    }

    #[Route(path: '/uuid', name: 'uuid', methods: ['GET', 'POST'])]
    public function uuid(
        Request $request,
        // UuidFactory $uuidFactory
    ): Response
    {
        $request_data = $request->request->all()['form'] ?? [];
        $uuids = [];
        // dump($request_data);
        $form = $this->createFormBuilder()
            ->add('uuid', TextType::class, [
                'label' => 'UUID',
                'required' => true,
                'data' => $request_data['uuid'] ?? '',
                // 'placeholder' => 'Enter a UUID',
            ])
            ->add('submit_generate_v7', SubmitType::class, [
                'label' => 'UUID v7',
                'row_attr' => ['class' => 'inline-block', 'data-turbo' => 'false'],
            ])
            ->add('submit_generate_v8', SubmitType::class, [
                'label' => 'UUID v8',
                'row_attr' => ['class' => 'inline-block', 'data-turbo' => 'false'],
            ])
            ->setMethod('POST')
            // ->setAttribute('data-turbo', 'false') // Disable Turbo for this form
            ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $form_data = $form->getData();
            // dump($form_data);
            // Actions with submit type
            foreach ($request_data as $name => $value) {
                if(preg_match('/^submit_(.+)$/', $name, $matches)) {
                    $action = $matches[1];
                    switch ($action) {
                        case 'generate_v7':
                            // Generate a new v7 UUID
                            try {
                                $uuids['form.'.$action] = Uuid::v7($form_data['uuid']);
                                $this->addFlash('success', 'Valid UUID v7 format.');
                            } catch (\InvalidArgumentException $e) {
                                $this->addFlash('error', 'Invalid UUID v7 format ('.json_encode($form_data['uuid']).').');
                                return $this->redirectToRoute('sadmin_sandbox_uuid');
                            }
                            break;
                        case 'generate_v8':
                            // Generate a new v8 UUID
                            try {
                                $uuids['form.'.$action] = Uuid::v8($form_data['uuid']);
                                $this->addFlash('success', 'Valid UUID v8 format.');
                            } catch (\InvalidArgumentException $e) {
                                $this->addFlash('error', 'Invalid UUID v8 format ('.json_encode($form_data['uuid']).').');
                                return $this->redirectToRoute('sadmin_sandbox_uuid');
                            }
                            break;
                        default:
                            $this->addFlash('error', 'Unknown action: '.$action);
                            return $this->redirectToRoute('sadmin_sandbox_uuid');
                    }
                }
            }
        }
        // $uuids['generic'] = $uuidFactory->create('550e8400-e29b-41d4-a716-446655440000'); // Example UUID
        // $uuids['v7'] = Uuid::v7('custom_name');
        return $this->render('@AequationWire/sadmin/sandbox_uuid.html.twig', [
            'form' => $form,
            'uuids' => $uuids,
        ]);
    }

}