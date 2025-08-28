<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Form\CategoryType;
use Aequation\WireBundle\Entity\interface\WireCategoryInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/category', name: 'admin_category_')]
#[IsGranted("ROLE_COLLABORATOR")]
class CategoryController extends EntityController
{

    public const ENTITY_CLASS = WireCategoryInterface::class;
    public const FORM_CLASS = CategoryType::class;

}
