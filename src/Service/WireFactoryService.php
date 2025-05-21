<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Service\interface\WireFactoryServiceInterface;

abstract class WireFactoryService extends WireItemService implements WireFactoryServiceInterface
{

    public const ENTITY_CLASS = WireFactory::class;

    public function getPreferedFactory(): ?WireFactoryInterface
    {
        return $this->getRepository()->findOneBy(['prefered' => true]);
    }

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireFactoryInterface entities
        $this->wireEm->decDebugMode();
        return $opresult;
    }

}