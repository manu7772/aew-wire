<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WireUrlink;
use Aequation\WireBundle\Service\interface\WireUrlinkServiceInterface;

class WireUrlinkService extends WireRelinkService implements WireUrlinkServiceInterface
{

    const ENTITY_CLASS = WireUrlink::class;

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireUrlinkInterface entities
        return $opresult;
    }

}