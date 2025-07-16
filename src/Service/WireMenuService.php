<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\WireMenu;
use Aequation\WireBundle\Service\interface\WireMenuServiceInterface;
use Aequation\WireBundle\Service\interface\WireWebpageServiceInterface;
use Aequation\WireBundle\Tools\Objects;
// PHP
use Exception;

abstract class WireMenuService extends WireEcollectionService implements WireMenuServiceInterface
{

    public const ENTITY_CLASS = WireMenu::class;
    // public const WP_DEFAULT_UNAME = 'wp_page_menu'; // Uname of the default Webpage for this entity

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireMenuInterface entities
        // 1. Check if each menu has a Webpage assigned
        foreach ($this->getRepository()->findAll() as $menu) {
            if(!$menu->getWebpage()) {
                if($repair) {
                    /** @var WireWebpageServiceInterface */
                    $webpageService = $this->getWireEm()->getEntityService(WireWebpageInterface::class);
                    $menu_webpage ??= $webpageService->getWebpageFor($menu);
                    if($menu_webpage) {
                        $menu->setWebpage($menu_webpage);
                        $this->getWireEm()->getEntityManager()->flush();
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
        array $data = [], // ---> do not forget uname if wanted!
        array $context = []
    ): WireMenuInterface
    {
        $entity = $this->getWireEm()->getEntitiesMetadata()->newInstance($this->getEntityClassname(), $data, $context);
        if($this->getWireEm()->isGrantsCheckEnabled() && !$this->appWire->isGranted('new', $entity->getClassname())) {
            throw new Exception(vsprintf('Error %s line %d: you are not allowed to create %s%s!', [__METHOD__, __LINE__, $this->getEntityClassname(), $entity->getClassname() !== $this->getEntityClassname() ? ' (initially requested '.$this->getEntityClassname().')' : '']));
        }
        /** @var WireWebpageServiceInterface */
        $webpageService = $this->getWireEm()->getEntityService(WireWebpageInterface::class);
        $menu_webpage = $webpageService->getWebpageFor($entity);
        if($menu_webpage) {
            $entity->setWebpage($menu_webpage);
        }
        return $entity;
    }

    public function getMainMenu(): ?WireMenuInterface
    {
        $menu = $this->findOneBy(null, ['prefered' => true]);
        return $menu instanceof WireMenuInterface ? $menu : null;
    }

}