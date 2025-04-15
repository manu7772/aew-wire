<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WireMenu;
use Aequation\WireBundle\Service\interface\WireMenuServiceInterface;

abstract class WireMenuService extends WireEcollectionService implements WireMenuServiceInterface
{

    public const ENTITY_CLASS = WireMenu::class;

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireMenuInterface entities
        $this->wireEm->decDebugMode();
        return $opresult;
    }

}