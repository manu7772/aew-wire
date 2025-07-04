<?php
namespace Aequation\WireBundle\Controller\Sadmin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/sadmin/metadata', name: 'sadmin_metadata_')]
#[IsGranted("ROLE_SUPER_ADMIN")]
class MetadataController extends AbstractController
{

    #[Route(path: '/sadmin/metadata', name: 'sadmin_metadata_')]
    public function debugToOptimize(
        DebugSadminInterface $debugSadmin
    ): Response
    {
        $toOptimize = $debugSadmin->getToOptimize();
        return $this->render('@AequationWire/sadmin/metadata.html.twig', [
        ]);
    }


}