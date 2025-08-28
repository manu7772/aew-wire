<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Form\FactoryType;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/factory', name: 'admin_factory_')]
#[IsGranted("ROLE_COLLABORATOR")]
class FactoryController extends EntityController
{

    public const ENTITY_CLASS = WireFactoryInterface::class;
    public const FORM_CLASS = FactoryType::class;

}
