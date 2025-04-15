<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireArticleInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Entity\WireArticle;
use Aequation\WireBundle\Service\interface\WireArticleServiceInterface;
use Aequation\WireBundle\Tools\Encoders;
// Symfony
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
// PHP
use DateTime;
use Doctrine\Common\Collections\Expr\Comparison;

abstract class WireArticleService extends WireItemService implements WireArticleServiceInterface
{

    public const ENTITY_CLASS = WireArticle::class;
    // Criteria
    // public const CRITERIA_ENABLED = ['enabled' => true];
    // public const CRITERIA_DISABLED = ['enabled' => false];

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireArticleInterface entities
        $this->wireEm->decDebugMode();
        return $opresult;
    }


}