<?php
namespace Aequation\WireBundle\Controller\Admin;

use Aequation\WireBundle\Form\ArticleType;
use Aequation\WireBundle\Entity\interface\WireArticleInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/article', name: 'admin_article_')]
#[IsGranted("ROLE_COLLABORATOR")]
class ArticleController extends EntityController
{

    public const ENTITY_CLASS = WireArticleInterface::class;
    public const FORM_CLASS = ArticleType::class;

}
