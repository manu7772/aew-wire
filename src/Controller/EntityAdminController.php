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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityAdminController extends AbstractController
{

    protected readonly array $entityNames;
    protected readonly WireEntityServiceInterface $entityService;
    protected readonly string|false $currentClassname;
    protected readonly string|false $currentShortname;

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

    public static function supports(string $entityName, array $entityNames): bool
    {
        if(array_key_exists($entityName, $entityNames)) {
            return empty(static::findSpecificControllerIfExists($entityNames[$entityName], null));
        }
        if(in_array(ucfirst($entityName), $entityNames)) {
            return empty(static::findSpecificControllerIfExists($entityName, null));
        }
        return false;
    }

    public static function findSpecificControllerIfExists(string $entityName, ?string $default = null): ?string
    {
        $controllerName = 'Aequation\\WireBundle\\Controller\\'.ucfirst($entityName).'AdminController';
        return class_exists($controllerName) ? $controllerName : $default;
    }

    /**
     * Check if entity name exists
     * 
     * @param string $entityName
     * @return bool --> true if entity is supported
     */
    public function checkAndLoadEntityNames(string $entityName): bool
    {
        $this->getEntityNames();
        if(!static::supports($entityName, $this->entityNames)) {
            $this->currentClassname = false;
            $this->currentShortname = false;
            return false;
        }
        switch (true) {
            case array_key_exists($entityName, $this->entityNames):
                // Classname given
                $this->currentClassname = $entityName;
                $this->currentShortname = $this->entityNames[$entityName];
                break;
            case in_array(ucfirst($entityName), $this->entityNames):
                // Shortname given
                $entityName = ucfirst($entityName);
                if($key = array_search($entityName, $this->entityNames)) {
                    $this->currentClassname = $this->entityNames[$key];
                    $this->currentShortname = $entityName;
                }
                break;
        }
        $this->currentClassname ??= false;
        $this->currentShortname ??= false;
        return $this->currentClassname !== false && $this->currentShortname !== false;
    }

    protected function loadEntityService(
        string $entityName
    ): WireEntityServiceInterface
    {
        if($this->checkAndLoadEntityNames($entityName)) {
            $this->entityService = $this->wire_em->getEntityService($entityName);
        } else {
            throw new EntityNotFoundException(vsprintf('Error %s line %d: entity %s not found', [__METHOD__, __LINE__, $entityName]));
        }
        return $this->entityService;
    }

    #[Route('/admin/{entity}', name: 'admin_entity_index', requirements: ['entity' => '\w+'], methods: ['GET'])]
    public function index(
        string $entity,
        Request $request
    ): Response
    {
        $this->loadEntityService($entity);
        $this->denyAccessUnlessGranted('index', $this->currentShortname, $this->translator->trans('access_denied'));
        if($this->entityService instanceof EntityServicePaginableInterface) {
            return $this->render('admin/'.strtolower($this->currentShortname).'/index.html.twig', $this->entityService->getPaginatedContextData($request));
        }
        throw new Exception(vsprintf('Error %s line %d: service %s for entity %s is not instance of %s, so not supported yet.', [__METHOD__, __LINE__, $this->entityService::class, EntityServicePaginableInterface::class, $this->currentClassname]));
    }

}