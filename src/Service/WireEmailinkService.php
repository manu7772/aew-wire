<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WireEmailink;
use Aequation\WireBundle\Service\interface\WireEmailinkServiceInterface;

class WireEmailinkService extends WireRelinkService implements WireEmailinkServiceInterface
{

    const ENTITY_CLASS = WireEmailink::class;

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEntityService->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireEmailinkInterface entities
        $this->wireEntityService->decDebugMode();
        return $opresult;
    }

}