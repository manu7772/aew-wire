<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Form\WireImageType;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/image', name: 'admin_image_')]
#[IsGranted("ROLE_COLLABORATOR")]
class ImageController extends EntityController
{

    public const ENTITY_CLASS = WireImageInterface::class;
    // public const FORM_CLASS = WireImageType::class;

}
