<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireArticle;
use Aequation\WireBundle\Repository\interface\WireArticleRepositoryInterface;
use Aequation\WireBundle\Repository\WireItemRepository;

/**
 * @extends WireItemRepository
 */
abstract class WireArticleRepository extends WireItemRepository implements WireArticleRepositoryInterface
{

    // const ENTITY_CLASS = WireArticle::class;
    // const NAME = 'wire_article';

}
