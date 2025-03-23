<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\WireArticle;
use Aequation\WireBundle\Service\interface\WireArticleServiceInterface;

abstract class WireArticleService extends WireItemService implements WireArticleServiceInterface
{

    public const ENTITY_CLASS = WireArticle::class;

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEntityService->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireArticleInterface entities
        $this->wireEntityService->decDebugMode();
        return $opresult;
    }

}