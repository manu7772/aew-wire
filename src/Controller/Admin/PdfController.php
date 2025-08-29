<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Form\WirePdfType;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pdf', name: 'admin_pdf_')]
#[IsGranted("ROLE_COLLABORATOR")]
class PdfController extends EntityController
{

    public const ENTITY_CLASS = WirePdfInterface::class;
    // public const FORM_CLASS = WirePdfType::class;

}
