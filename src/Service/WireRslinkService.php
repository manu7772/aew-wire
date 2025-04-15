<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WireRslink;
use Aequation\WireBundle\Service\interface\WireRslinkServiceInterface;

class WireRslinkService extends WireRelinkService implements WireRslinkServiceInterface
{

    const ENTITY_CLASS = WireRslink::class;

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireRslinkInterface entities
        $this->wireEm->decDebugMode();
        return $opresult;
    }

}