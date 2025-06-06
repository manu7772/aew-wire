<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\WireMenu;
use Aequation\WireBundle\Service\interface\WireMenuServiceInterface;
use Aequation\WireBundle\Service\interface\WireWebpageServiceInterface;
use Aequation\WireBundle\Tools\Objects;

abstract class WireMenuService extends WireEcollectionService implements WireMenuServiceInterface
{

    public const ENTITY_CLASS = WireMenu::class;
    // public const WP_DEFAULT_UNAME = 'wp_page_menu'; // Uname of the default Webpage for this entity

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireMenuInterface entities
        // 1. Check if each menu has a Webpage assigned
        foreach ($this->getRepository()->findAll() as $menu) {
            if(!$menu->getWebpage()) {
                if($repair) {
                    /** @var WireWebpageServiceInterface */
                    $webpageService = $this->wireEm->getEntityService(WireWebpageInterface::class);
                    $menu_webpage ??= $webpageService->getWebpageFor($menu);
                    if($menu_webpage) {
                        $menu->setWebpage($menu_webpage);
                        $this->wireEm->getEntityManager()->flush();
                        $opresult->addSuccess(vsprintf('Webpage "%s" assigned to menu "%s".', [
                            Objects::toDump($menu_webpage),
                            Objects::toDump($menu)
                        ]));
                    }
                } else {
                    $opresult->addWarning(vsprintf('%s has no Webpage assigned. Please assign a Webpage to the menu.', [Objects::toDump($menu)]));
                }
            }
        }
        $this->wireEm->decDebugMode();
        return $opresult;
    }

    /**
     * Create a new WireMenu entity.
     * 1. Add Wepage (Uname: "wp_page_menu") to the menu.
     * 
     * @param array|false $data
     * @param array $context
     * @return WireMenuInterface
     */
    public function createEntity(
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = []
    ): WireMenuInterface
    {
        /** @var WireMenuInterface */
        $entity = $this->wireEm->createEntity($this->getEntityClassname(), $data, $context, false); // false = do not try service IMPORTANT!!!
        // 1. Add Wepage (Uname: "wp_page_menu") to the menu
        /** @var WireWebpageServiceInterface */
        $webpageService = $this->wireEm->getEntityService(WireWebpageInterface::class);
        $menu_webpage = $webpageService->getWebpageFor($entity);
        if($menu_webpage) {
            $entity->setWebpage($menu_webpage);
        }
        return $entity;
    }

}