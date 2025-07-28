<?php
namespace Aequation\WireBundle\Controller\Hydration;

use Aequation\WireBundle\Component\interface\HydradataCollectionInterface;
use Aequation\WireBundle\Component\interface\HydradataItemsInterface;
use Aequation\WireBundle\Service\interface\HydrationServiceInterface;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/show/{name}', name: 'show')]
    public function show(string $name): Response
    {
        $datas = $this->hydrator->getHydatableData();
        $hydradataItems = $datas->getByName($name) ?? $datas->getByShortname($name);
        return $this->render('@AequationWire/hydration/show.html.twig', [
            'name' => $name,
            'hydradataItems' => $hydradataItems,
            'others' => $this->getOthers($hydradataItems),
        ]);
    }

    #[Route('/generate/{data}', name: 'generate')]
    public function generate(
        string $data,
        Request $request
    ): Response
    {
        $indexes = $this->hydrator->requestDataToIndexes($data);
        $new_indexes = [];
        $opresults = [];
        foreach ($indexes as $item) {
            $opresults[$item['index']] = $this->hydrator->generate($item['index'], $item['items'], true);
            $new_indexes[$item['index']] = $opresults[$item['index']]->isSuccess(); // Store the index for redirection
            foreach ($opresults[$item['index']]->getMessagesTypedForFlash() as $type => $messages) {
                foreach ($messages as $message) {
                    $this->addFlash($type, $message);
                }
            }
        }
        return ($referer = $request->headers->get('referer'))
            ? $this->redirect($referer)
            : $this->redirectToRoute('hydration_index', ['indexes' => json_encode($new_indexes)]);
    }

    #[Route('/test/{data}', name: 'test')]
    public function test(
        string $data
    ): Response
    {
        $indexes = $this->hydrator->requestDataToIndexes($data);
        $opresults = [];
        $links = [
            'valids' => [],
            'invalids' => [],
            'count' => 0,
            'count_valids' => 0,
            'count_invalids' => 0,
            // 'valid' => true,
        ];
        foreach ($indexes as $item) {
            $opresults[$item['index']] = $this->hydrator->generate($item['index'], $item['items'], false);
            // foreach ($opresult->getMessagesTypedForFlash() as $type => $messages) {
            //     foreach ($messages as $message) {
            //         $this->addFlash($type, $message);
            //     }
            // }
            foreach ($opresults as $index => $results) {
                if($results->isSuccess()) {
                    $links['valids'][$index] ??= [];
                    foreach ($results->getData() as $data) {
                        $links['valids'][$index][$data['item_index']] = $data['item_index'];
                        $links['count']++;
                        $links['count_valids']++;
                    }
                    $links['valids'][$index] = array_values($links['valids'][$index]);
                } else {
                    $links['invalids'][$index] ??= [];
                    foreach ($results->getData() as $data) {
                        $links['invalids'][$index][$data['item_index']] = $data['item_index'];
                        $links['count']++;
                        $links['count_invalids']++;
                    }
                    $links['invalids'][$index] = array_values($links['invalids'][$index]);
                }
            }
        }
        $links['valids'] = array_filter($links['valids'], fn($link) => !empty($link));
        $links['invalids'] = array_filter($links['invalids'], fn($link) => !empty($link));
        $links['valid'] = empty($links['invalids']);
        return $this->render('@AequationWire/hydration/test.html.twig', [
            'links' => $links,
            'opresults' => array_values($opresults),
            'indexes' => $indexes,
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
            // ->getByName($hydradataItems->name)
            ->filter(
                fn (HydradataItemsInterface $item) => $item->name === $hydradataItems->name && $item->isValid() && $item->getIndex() !== $hydradataItems->getIndex()
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