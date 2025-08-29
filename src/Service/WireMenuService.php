<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\WireMenu;
use Aequation\WireBundle\Form\WireMenuType;
use Aequation\WireBundle\Service\interface\WireMenuServiceInterface;
use Aequation\WireBundle\Service\interface\WireWebpageServiceInterface;
use Aequation\WireBundle\Tools\Objects;
// PHP
use Exception;

abstract class WireMenuService extends WireEcollectionService implements WireMenuServiceInterface
{

    public const ENTITY_CLASS = WireMenu::class;
    public const ENTITY_TYPE = WireMenuType::class;
    // public const WP_DEFAULT_UNAME = 'wp_page_menu'; // Uname of the default Webpage for this entity

    public function checkDatabase(OpresultInterface $opresult, bool $repair = false, array $options = []): void
    {
        parent::checkDatabase($opresult, $repair);
        $this->paginatedAction(
            callback: function ($entity) use ($opresult, $repair) {
                /** @var WireMenuInterface $entity */
                $this->entityCheckActions($entity, $opresult);
                // if(!$entity->getWebpage()) {
                //     if($repair) {
                //         /** @var WireWebpageServiceInterface */
                //         $webpageService ??= $this->getWireEm()->getEntityService(WireWebpageInterface::class);
                //         if($menu_webpage = $webpageService->getFirstExposableWebpage($entity, true, true)) {
                //             $this->getWireEm()->getEntityManager()->flush();
                //             $opresult->addSuccess(vsprintf('Webpage "%s" assigned to menu "%s".', [
                //                 Objects::toDebugString($menu_webpage),
                //                 Objects::toDebugString($entity)
                //             ]));
                //         }
                //     } else if($entity->isWebpageRequired()) {
                //         $opresult->addWarning(vsprintf('%s has no Webpage assigned. Please assign a Webpage to the menu.', [Objects::toDebugString($entity)]));
                //     }
                // }
                return $repair;
            },
            options: array_merge(static::DEFAULT_CHECK_DB_OPTIONS, $options)
        );
    }

    // public function entityEventActions(BaseEntityInterface $entity, ?OpresultInterface $opresult = null): void
    // {
    //     if(!is_a($entity, static::ENTITY_CLASS)) {
    //         if($this->appWire->isDev()) throw new Exception(vsprintf('Error %s line %d: entity %s is not a %s!', [__METHOD__, __LINE__, Objects::getClassname($entity), static::ENTITY_CLASS]));
    //     }
    //     if($entity->getSelfState()->isNew()) {
    //         // After created actions...
    //         $this->wireEm->defaultEntityEventActions($entity, $opresult);
    //     }
    //     if($entity->getSelfState()->isLoaded()) {
    //         // After loaded actions...
    //         $this->wireEm->defaultEntityEventActions($entity, $opresult);
    //     }
    //     // After all actions...
    // }

    public function getMainMenu(): ?WireMenuInterface
    {
        $menu = $this->findOneBy(null, ['prefered' => true]);
        return $menu instanceof WireMenuInterface ? $menu : null;
    }

}