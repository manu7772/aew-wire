<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\WireWebpage;
use Aequation\WireBundle\Repository\interface\WireWebpageRepositoryInterface;

/**
 * @extends WireWebpageRepository
 */
abstract class WireWebpageRepository extends WireItemRepository implements WireWebpageRepositoryInterface
{

    const NAME = WireWebpage::class;
    const ALIAS = 'wirewebpage';

    public function findExposables(bool $onlyActive = false): array
    {
        $qb = $this->newAliasBuilder();
        if ($onlyActive) {
            $qb->andWhere(static::ALIAS.'.enabled = true');
        }
        $qb->andWhere(static::ALIAS.'.wpexposes.elements != :c')
            ->setParameter('c', json_encode([]));
        return $qb->getQuery()->getResult();
    }

    public function findExposablesFor(string|object $item, bool $onlyActive = false): array
    {
        $exposables = $this->findExposables($onlyActive);
        // dump($exposables);
        return array_filter($exposables, fn (WireWebpageInterface $wp) => $wp->isAvailableForExpose($item));
    }

}
