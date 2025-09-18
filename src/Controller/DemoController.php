<?php
namespace Aequation\WireBundle\Controller;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Tools\Files;
use Aequation\WireBundle\Tools\Strings;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_demo', name: 'demo_')]
final class DemoController extends AbstractController
{

    #[Route('/page/{page}', name: 'page', defaults: ['page' => null])]
    public function index(
        AppWireServiceInterface $appWire,
        ?string $page = null
    ): Response
    {
        $templatesDir = $appWire->getProjectDir('templates/');
        $pages = [];
        foreach (Files::findPagesTemplates($templatesDir, "{# APP WIRE DEMO PAGE #}") as $finder) {
            $pathname = $finder->getPathname();
            $pages[$pathname] = [
                'template' => Strings::getAfter($pathname, $templatesDir),
                'filename' => Files::stripTwigfile($finder, true),
                'name' => ucfirst(preg_replace('/[_+]/', ' ', Files::stripTwigfile($finder, true))),
            ];
            if($page === $pages[$pathname]['filename']) {
                $page = $pages[$pathname];
            }
        }
        ksort($pages);
        if(!is_array($page)) {
            $page = reset($pages);
        }
        $this->addFlash('info', 'Loaded demo page "'.$page['name'].'" (File '.$page['filename'].')');
        return $this->render($page['template'], [
            'page' => $page,
            'pages' => $pages,
        ]);
    }
}
