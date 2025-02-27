<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireArticle;
use Aequation\WireBundle\Service\interface\WireArticleServiceInterface;

abstract class WireArticleService extends WireItemService implements WireArticleServiceInterface
{

    public const ENTITY_CLASS = WireArticle::class;

}