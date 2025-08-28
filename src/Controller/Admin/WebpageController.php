<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Form\WebpageType;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/webpage', name: 'admin_webpage_')]
#[IsGranted("ROLE_COLLABORATOR")]
class WebpageController extends EntityController
{

    public const ENTITY_CLASS = WireWebpageInterface::class;
    public const FORM_CLASS = WebpageType::class;

}
