<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
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
                    if($menu_webpage = $webpageService->getFirstExposableWebpage($menu, true, true)) {
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

    public function entityEventActions(
        BaseEntityInterface $entity
    ): void
    {
        if(!is_a($entity, static::ENTITY_CLASS)) {
            if($this->appWire->isDev()) throw new Exception(vsprintf('Error %s line %d: entity %s is not a %s!', [__METHOD__, __LINE__, Objects::getClassname($entity), static::ENTITY_CLASS]));
        }
        if($entity->getSelfState()->isNew()) {
            // After created actions...
            $this->wireEm->defaultEntityEventActions($entity);
        }
        if($entity->getSelfState()->isLoaded()) {
            // After loaded actions...
            $this->wireEm->defaultEntityEventActions($entity);
        }
        // After all actions...
    }

    public function getMainMenu(): ?WireMenuInterface
    {
        $menu = $this->findOneBy(null, ['prefered' => true]);
        return $menu instanceof WireMenuInterface ? $menu : null;
    }

}