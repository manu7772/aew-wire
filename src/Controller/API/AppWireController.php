<?php
namespace Aequation\WireBundle\Controller\API;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
// PHP
use Exception;

#[Route(path: '/api', name: 'aequation_wire_api.')]
// #[IsGranted("ROLE_USER", null, 'forbidden_access', 403)]
class AppWireController extends AbstractController
{

    #[Route(path: '/tiny-set/{name}/{value}', name: 'set_tiny', methods: ['GET','POST'])]
    public function setTinyvalue(
        AppWireServiceInterface $appWire,
        string $name,
        string $value
    ): JsonResponse
    {
        $appWire->setTinyvalue($name, $value, true);
        return $this->json(
            data: ['value' => $appWire->getTinyvalue($name)],
            status: JsonResponse::HTTP_OK,
            context: $appWire->jsonSerialize(),
        );
    }

    #[Route(path: '/tiny-bool/{name}/{action<(switch|true|false)>?switch}', name: 'bool_tiny', methods: ['GET','POST'])]
    public function boolTinyvalue(
        AppWireServiceInterface $appWire,
        string $name,
        string $action = 'switch'
    ): JsonResponse
    {
        if(!in_array($action, ['switch'])) {
            $action = (bool)$action;
        }
        switch ($action) {
            case true:
                $appWire->setTinyvalue($name, true, true);
                break;
            case false:
                $appWire->setTinyvalue($name, false, true);
            default:
                $appWire->setTinyvalue($name, $appWire->getTinyvalue($name, true), true);
                break;
        }
        return $this->json(
            data: ['value' => $appWire->getTinyvalue($name)],
            status: JsonResponse::HTTP_OK,
            context: $appWire->jsonSerialize(),
        );
    }

}