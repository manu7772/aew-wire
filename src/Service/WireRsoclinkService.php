<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WireRsoclink;
use Aequation\WireBundle\Service\interface\WireRsoclinkServiceInterface;

class WireRsoclinkService extends WireRelinkService implements WireRsoclinkServiceInterface
{

    const ENTITY_CLASS = WireRsoclink::class;

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireRsoclinkInterface entities
        return $opresult;
    }

}