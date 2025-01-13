<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\PdfizableInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WirePdfServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;
// PHP
use DateTimeImmutable;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[AsAlias(WirePdfServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class WirePdfService extends WireItemService implements WirePdfServiceInterface
{
    public const ENTITY_CLASS = WirePdfInterface::class;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService,
        protected DompdfFactoryInterface $dompdfFactory,
        protected UploaderHelper $vichHelper,
    )
    {
        parent::__construct($appWire, $wireEntityService);
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