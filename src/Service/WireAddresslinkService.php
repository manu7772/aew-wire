<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WireAddresslink;
use Aequation\WireBundle\Service\interface\WireAddresslinkServiceInterface;

class WireAddresslinkService extends WireRelinkService implements WireAddresslinkServiceInterface
{

    const ENTITY_CLASS = WireAddresslink::class;

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEntityService->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireAddresslinkInterface entities
        $this->wireEntityService->decDebugMode();
        return $opresult;
    }

}