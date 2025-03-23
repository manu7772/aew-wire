<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Service\interface\WireFactoryServiceInterface;

abstract class WireFactoryService extends WireItemService implements WireFactoryServiceInterface
{

    public const ENTITY_CLASS = WireFactory::class;

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEntityService->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireFactoryInterface entities
        $this->wireEntityService->decDebugMode();
        return $opresult;
    }

}