<?php
namespace Aequation\WireBundle\Controller\Entities;

// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/classmetadata', name: 'classmetadata_')]
class MetadataController extends AbstractController
{

    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('@AequationWire/classmetadata/index.html.twig');
    }

}