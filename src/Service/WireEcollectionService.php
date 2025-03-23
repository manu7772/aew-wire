<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WireEcollection;
use Aequation\WireBundle\Service\interface\WireEcollectionServiceInterface;

abstract class WireEcollectionService extends WireItemService implements WireEcollectionServiceInterface
{

    public const ENTITY_CLASS = WireEcollection::class;

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEntityService->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireEcollectionInterface entities
        $this->wireEntityService->decDebugMode();
        return $opresult;
    }

}