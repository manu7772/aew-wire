<?php
namespace Aequation\WireBundle\Controller\Hydration;

use Aequation\WireBundle\Component\HydradataItems;
use Aequation\WireBundle\Component\interface\HydradataCollectionInterface;
use Aequation\WireBundle\Component\interface\HydradataItemsInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\HydrationServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/hydration', name: 'hydration_')]
class HydrationController extends AbstractController
{

    public function __construct(
        protected HydrationServiceInterface $hydrator
    ) {}

    #[Route('/{indexes}', name: 'index', defaults: ['indexes' => '[]'])]
    public function index(string $indexes): Response
    {
        return $this->render('@AequationWire/hydration/index.html.twig', [
            'indexes' => array_unique((array) json_decode($indexes)),
            'hydradataItems' => $this->hydrator->getHydatableData(),
        ]);
    }

    #[Route('/show/{index}', name: 'show')]
    public function show(string $index): Response
    {
        $datas = $this->hydrator->getHydatableData();
        $hydradataItems = $datas->get($index);
        return $this->render('@AequationWire/hydration/show.html.twig', [
            'index' => $index,
            'hydradataItems' => $hydradataItems,
            'others' => $this->getOthers($hydradataItems),
        ]);
    }

    #[Route('/generate/{index}', name: 'generate')]
    public function generate(string $index): Response
    {
        $indexes = array_unique((array) json_decode($index));
        $new_indexes = [];
        foreach ($indexes as $key => $index) {
            if(is_array($index)) {
                $items = $index;
                $index = $key; // Handle single index in array
            } else {
                $index = (int) $index; // Ensure index is an integer
                $items = [];
            }
            $opresult = $this->hydrator->generate($index, $items, true);
            $new_indexes[$index] = true; // Store the index for redirection
            foreach ($opresult->getMessagesTypedForFlash() as $type => $messages) {
                foreach ($messages as $message) {
                    $this->addFlash($type, $message);
                }
            }
        }
        return $this->redirectToRoute('hydration_index', [
            'indexes' => json_encode($new_indexes),
        ]);
        // return $this->render('@AequationWire/hydration/index.html.twig', [
        //     'indexes' => $indexes,
        //     'hydradataItems' => $datas,
        // ]);
    }

    #[Route('/test/{index}', name: 'test')]
    public function test(string $index): Response
    {
        $indexes = array_unique((array) json_decode($index));
        $new_indexes = [];
        foreach ($indexes as $key => $index) {
            if(is_array($index)) {
                $items = $index;
                $index = $key; // Handle single index in array
            } else {
                $index = (int) $index; // Ensure index is an integer
                $items = [];
            }
            $opresult = $this->hydrator->generate($index, $items, false);
            $new_indexes[$index] = true; // Store the index for redirection
            foreach ($opresult->getMessagesTypedForFlash() as $type => $messages) {
                foreach ($messages as $message) {
                    $this->addFlash($type, $message);
                }
            }
        }
        // return $this->redirectToRoute('hydration_index', [
        //     'indexes' => json_encode($new_indexes),
        // ]);
        return $this->render('@AequationWire/hydration/test.html.twig', [
            'indexes' => $indexes,
            'hydradataItems' => $this->hydrator->getHydatableData(),
        ]);
    }

    protected function getOthers($hydradataItems): ?HydradataCollectionInterface
    {
        // This method can be overridden to provide additional logic for fetching "others"
        if(!$hydradataItems || !$hydradataItems->isValid()) {
            return null;
        }
        return $this->hydrator
            ->getHydatableData()
            ->getByName($hydradataItems->name)
            ->filter(
                fn (HydradataItemsInterface $item) => $item->isValid() && $item->getIndex() !== $hydradataItems->getIndex()
            );
    }


    // #[Route('/user', name: 'user')]
    // public function user(
    //     AppWireServiceInterface $appWire,
    //     WireUserServiceInterface $service,
    //     ObjectMapperInterface $objectMapper
    // ): Response
    // {
    //     $data = [
    //         'email' => $appWire->getParam('main_sadmin'),
    //         'name' => 'Dujardin',
    //         'firstname' => 'Emmanuel',
    //         'plainPassword' => 'sadmin',
    //         'uname' => 'super_admin_manu',
    //         'description' => 'Super Admin Emmanuel Dujardin',
    //         'superadmin' => true,
    //         'enabled' => true,
    //         'roles' => ['ROLE_SUPER_ADMIN'],
    //     ];
    //     $dto = $service->createDto($data);
    //     /** @var WireUserInterface */
    //     $user = $objectMapper->map($dto, $service->createEntity());
    //     // $user->setSuperadmin();
    //     $newDto = $objectMapper->map($user, $dto::class);

    //     return $this->render('@AequationWire/hydration/user.html.twig', [
    //         'data' => $data,
    //         'dto' => $dto,
    //         'user' => $user,
    //         'newDto' => $newDto,
    //     ]);
    // }

}