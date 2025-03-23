<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WirePhonelink;
use Aequation\WireBundle\Service\interface\WirePhonelinkServiceInterface;

class WirePhonelinkService extends WireRelinkService implements WirePhonelinkServiceInterface
{

    const ENTITY_CLASS = WirePhonelink::class;

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEntityService->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WirePhonelinkInterface entities
        $this->wireEntityService->decDebugMode();
        return $opresult;
    }

}