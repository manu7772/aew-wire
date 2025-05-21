<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireArticle;
use Aequation\WireBundle\Repository\interface\WireArticleRepositoryInterface;
use Aequation\WireBundle\Repository\WireItemRepository;
use DateTime;
// Symfony
use Doctrine\ORM\QueryBuilder;

/**
 * @extends WireItemRepository
 */
abstract class WireArticleRepository extends WireItemRepository implements WireArticleRepositoryInterface
{

    const NAME = WireArticle::class;
    const ALIAS = 'wire_article';


    public function findAllActivesQueryBuilder(QueryBuilder $qb): void
    {
        $qb
            ->andWhere(static::alias().'.enabled = 1')
            ->andWhere(static::alias().'.start >= :now')
            ->andWhere(static::alias().'.end <= :now')
            ->setParameter('now', new DateTime('now'))
            ;
    }

    public function findAllInactivesQueryBuilder(QueryBuilder $qb): void
    {
        $qb
            ->andWhere(static::alias().'.enabled = 0')
            ->orWhere(static::alias().'.start < :now')
            ->orWhere(static::alias().'.end > :now')
            ->setParameter('now', new DateTime('now'))
            ;
    }

}
