<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WireImage;
use Aequation\WireBundle\Service\interface\WireImageServiceInterface;

abstract class WireImageService extends WireItemService implements WireImageServiceInterface
{

    public const ENTITY_CLASS = WireImage::class;

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEntityService->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireImageInterface entities
        $this->wireEntityService->decDebugMode();
        return $opresult;
    }

}