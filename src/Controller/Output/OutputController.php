<?php
namespace Aequation\WireBundle\Controller\Output;

use Aequation\WireBundle\Component\interface\PdfizableInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WirePdfServiceInterface;
// Symfony
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

#[Route('/output', name: 'output.')]
class OutputController extends AbstractController
{

    public function __construct(
        protected WireEntityManagerInterface $wire_em,
        protected ?WirePdfServiceInterface $pdfService
    ) {}

    protected function getOutputResponse(
        PdfizableInterface $pdf,
        string $action
    ): Response
    {
        if(!$this->pdfService) {
            throw $this->createNotFoundException('Le service de génération de PDF n\'est pas disponible');
        }
        $response = new Response(status: Response::HTTP_OK);
        $response->headers->set('Content-Type', $pdf->getMime());
        $response->headers->set('Content-Disposition', $action.'; filename="' . $pdf->getFilename() . '"');
        if($pdf instanceof WirePdfInterface && $pdf->getSourcetype() === 2) {
            if($path = $pdf->getFilepathname()) {
                dd($path, file_exists($path));
                return $this->redirect($path, Response::HTTP_FOUND);
            }
        }
        $response->setContent($this->pdfService->outputDoc($pdf));
        return $response;
    }

    #[Route('/pdf/{action<(inline|attachment)>}/{pdf}/{paper}/{orientation}', name: 'pdf_action', methods: ['GET'], defaults: ['action' => 'inline', 'paper' => null, 'orientation' => 'portrait'])]
    public function pdfOutputAction(
        string $pdf,
        string $action = 'inline',
        string $paper = null,
        string $orientation = 'portrait'
    ): Response
    {
        if(!$this->pdfService) {
            throw $this->createNotFoundException('Le service de génération de PDF n\'est pas disponible');
        }
        $doc = $this->wire_em->findEntityByUniqueValue($pdf);
        /** @var ServiceEntityRepository $repo */
        $repo = $this->pdfService->getRepository();
        $doc ??= $repo->find($pdf);
        $doc ??= $repo->findOneBy(['slug' => $pdf]);
        if($doc instanceof PdfizableInterface) {
            return $this->getOutputResponse($doc, $action);
        }
        throw $this->createNotFoundException(vsprintf('Le document %s n\'existe pas', [$pdf]));
    }


}