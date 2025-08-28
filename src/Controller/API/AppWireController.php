<?php
namespace Aequation\WireBundle\Controller\API;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
// PHP
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Route(path: '/api', name: 'aequation_wire_api.')]
class AppWireController extends AbstractController
{

    // #[Route(path: '/appwire/data', name: 'appwire_data', methods: ['GET'])]
    // public function getAppWireData(
    //     AppWireServiceInterface $appWire
    // ): JsonResponse
    // {
    //     return $this->json(
    //         data: $appWire->jsonSerialize(),
    //         status: JsonResponse::HTTP_OK,
    //     );
    // }

    #[Route(path: '/csstheme/get', name: 'csstheme_get', methods: ['GET'])]
    public function getCsstheme(
        AppWireServiceInterface $appWire
    ): JsonResponse
    {
        /** @var WireUserInterface */
        $user = $this->getUser();
        return $this->json(
            data: $user ? $user->getCsstheme() : $appWire->getCsstheme(),
            status: JsonResponse::HTTP_OK,
        );
    }

    #[Route('/csstheme/{firewall}/{csstheme}', name: 'csstheme_define', defaults: ['csstheme' => '__toggle__'], methods: ['GET','POST'])]
    public function cssthemeSwitcher(
        AppWireServiceInterface $appWire,
        string $firewall,
        string $csstheme = '__toggle__',
        // Request $request
    ): Response|JsonResponse
    {
        switch ($csstheme) {
            case '__toggle__':
                $appWire->toggleCsstheme($firewall);
                break;
            default:
                $appWire->setCsstheme($csstheme, $firewall);
                break;
        }
        if($appWire->isTurboFrameRequest()) {
            // If the request is turbo-frame, we return the HTML for the switcher
            return $this->render(
                '@AequationWire/components/csstheme-switcher.html.twig',
                [
                    'attributes' => null,
                    'id' => 'ts-header',
                    'icon' => 'tabler:user',
                    'firewall' => $firewall,
                    // 'firewall' => $appWire->getFirewallName(),
                ]
            );
        }
        return $this->json(
            data: ['csstheme' => $appWire->getCsstheme($firewall)],
            status: JsonResponse::HTTP_OK,
            // context: $appWire->jsonSerialize(),
        );
    }


    #[Route(path: '/tiny-set/{name}/{value}', name: 'set_tiny', methods: ['GET','POST'])]
    // #[IsGranted("ROLE_USER", null, 'forbidden_access', 403)]
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
    // #[IsGranted("ROLE_USER", null, 'forbidden_access', 403)]
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