<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Form\WireUserType;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
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

#[Route('/admin/user', name: 'admin_user_')]
#[IsGranted("ROLE_COLLABORATOR")]
class UserController extends EntityController
{

    public const ENTITY_CLASS = WireUserInterface::class;
    // public const FORM_CLASS = WireUserType::class;

}
