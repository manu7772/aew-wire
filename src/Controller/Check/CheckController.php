<?php
namespace Aequation\WireBundle\Controller\Check;

// Symfony

use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sadmin/check', name: 'check_')]
class CheckController extends AbstractController
{

    #[Route('/', name: 'index')]
    public function index(
        WireEntityManagerInterface $wireEm,
    ): Response
    {
        return $this->render('@AequationWire/check/index.html.twig', [
            'entities' => $wireEm->getEntitiesMetadata()->setSearchMode("final")->filterClasses([BaseEntityInterface::class]),
        ]);
    }

    #[Route('/entity/{entity}/{repair}', name: 'entity', requirements: ['entity' => '[a-zA-Z0-9_]+', 'repair' => 'true|false'], defaults: ['repair' => 'false'])]
    public function entity(
        string $entity,
        WireEntityManagerInterface $wireEm,
        string $repair = 'false',
    ): Response
    {
        $metadata_info = $wireEm->getEntityMetadata($entity);
        $service = $metadata_info->getService();
        $report = new Opresult();
        $service->checkDatabase($report, $repair);
        return $this->render('@AequationWire/check/entity.html.twig', [
            'metadata_info' => $metadata_info,
            'report' => $report,
            'repair' =>  $repair === 'true',
        ]);
    }


}