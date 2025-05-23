<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\PdfizableInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Entity\WirePdf;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WirePdfServiceInterface;
// Symfony
use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
// PHP
use DateTimeImmutable;
use Knp\Component\Pager\PaginatorInterface;

abstract class WirePdfService extends WireItemService implements WirePdfServiceInterface
{
    public const ENTITY_CLASS = WirePdf::class;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEm,
        protected PaginatorInterface $paginator,
        protected NormalizerServiceInterface $normalizer,
        protected DompdfFactoryInterface $dompdfFactory,
        protected UploaderHelper $vichHelper,
    )
    {
        parent::__construct($appWire, $wireEm, $paginator, $normalizer);
    }

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WirePdfInterface entities
        $this->wireEm->decDebugMode();
        return $opresult;
    }

    /**
     * Output a PDF from HTML content
     * 
     * @param string $htmlContent
     * @param string $paper
     * @param string $orientation
     * @param array $options
     * @return string
     */
    public function outputHtml(
        string $htmlContent,
        string $paper = 'A4',
        string $orientation = 'portrait',
        array $options = []
    ): string
    {
        $dompdf = $this->dompdfFactory->create($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        return $dompdf->output();
    }

    /**
     * Get the browser path of a PDF
     * 
     * @param WirePdfInterface $pdf
     * @return string
     */
    public function getBrowserPath(
        WirePdfInterface $pdf,
    ): string
    {
        $browserPath = $this->vichHelper->asset($pdf);
        return $browserPath;
    }

    /**
     * Output a PDF from a PdfizableInterface
     * 
     * @param PdfizableInterface $pdf
     * @return string
     */
    public function outputDoc(
        PdfizableInterface $pdf
    ): string
    {
        $template = $this->appWire->twig->createTemplate($pdf->getContent(), $pdf->getFilename());
        $htmlContent = $template->render(['date' => new DateTimeImmutable()]);
        return $this->outputHtml($htmlContent, $pdf->getPaper(), $pdf->getOrientation());
    }


}