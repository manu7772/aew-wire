<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Form\WireWebsectionType;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/websection', name: 'admin_websection_')]
#[IsGranted("ROLE_COLLABORATOR")]
class WebsectionController extends EntityController
{

    public const ENTITY_CLASS = WireWebsectionInterface::class;
    // public const FORM_CLASS = WireWebsectionType::class;

}
