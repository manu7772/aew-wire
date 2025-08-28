<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Entity\interface\WireLanguageInterface;
use Aequation\WireBundle\Form\LanguageType;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireLanguageServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/language', name: 'admin_language_')]
#[IsGranted("ROLE_COLLABORATOR")]
final class LanguageController extends EntityController
{

    public const ENTITY_CLASS = WireLanguageInterface::class;
    public const FORM_CLASS = LanguageType::class;

}
