<?php
namespace Aequation\WireBundle\Controller;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\EntityServicePaginableInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
// Symfony
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardAdminController extends AbstractController
{

    protected readonly array $entityNames;
    public function __construct(
        protected AppWireServiceInterface $app_wire,
        protected WireEntityManagerInterface $wire_em,
        protected EntityManagerInterface $entityManager,
        protected TranslatorInterface $translator
    ) {
    }

    /**
     * Get instantiable entities
     * 
     * @return array
     */
    protected function getEntityNames(): array
    {
        return $this->entityNames ??= $this->wire_em->getEntityNames(true, false, true);
    }


    public function dashboard(
        Request $request
    ): Response
    {
        return $this->render('admin/index.html.twig', [
            'entityNames' => $this->getEntityNames(),
        ]);
    }

}