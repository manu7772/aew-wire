<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\PdfizableInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;

interface WirePdfServiceInterface extends WireItemServiceInterface
{

    public function outputHtml(string $htmlContent, string $paper = 'A4', string $orientation = 'portrait', array $options = []): string;
    public function getBrowserPath(WirePdfInterface $pdf): string;
    public function outputDoc(PdfizableInterface $pdf): string;

}
