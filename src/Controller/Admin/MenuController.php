<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Form\WireMenuType;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
// PHP
use RuntimeException;

#[Route('/admin/menu', name: 'admin_menu_')]
#[IsGranted("ROLE_COLLABORATOR")]
class MenuController extends EntityController
{

    public const ENTITY_CLASS = WireMenuInterface::class;
    // public const FORM_CLASS = WireMenuType::class;

}
