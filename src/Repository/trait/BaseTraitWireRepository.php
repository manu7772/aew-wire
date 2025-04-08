<?php

namespace Aequation\WireBundle\Repository\trait;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
// Symfony
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\QueryBuilder;

trait BaseTraitWireRepository
{

    /*************************************************************************************************/
    /** BASE TOOLS                                                                                   */
    /*************************************************************************************************/

    public function hasField(string $name): bool
    {
        $cmd = $this->getClassMetadata();
        return array_key_exists($name, $cmd->fieldMappings);
    }

    public function hasRelation(string $name): bool
    {
        $cmd = $this->getClassMetadata();
        return array_key_exists($name, $cmd->associationMappings);
    }

    public static function alias(): string
    {
        return static::getDefaultAlias();
    }

    protected static function getAlias(QueryBuilder $qb): string
    {
        $from = $qb->getDQLPart('from');
        /** @var From */
        $from = reset($from);
        $aliases = $qb->getRootAliases();
        if ($from instanceof From) return $from->getAlias();
        return count($aliases) ? reset($aliases) : static::getDefaultAlias();
    }

    protected static function getFrom(QueryBuilder $qb): ?string
    {
        $from = $qb->getDQLPart('from');
        /** @var From */
        $from = reset($from);
        return $from instanceof From ? $from->getFrom() : null;
    }

    public function findEntityByEuidOrUname(
        string $euidOrUname
    ): ?BaseEntityInterface {
        $qb = $this->createQueryBuilder(static::alias())
            ->where(static::alias() . '.euid = :euidOrUname')
            ->setParameter('euidOrUname', $euidOrUname);
        if ($this->hasRelation('uname')) {
            $qb->leftJoin(static::alias() . '.uname', 'uname')
                ->orWhere('uname.id = :euidOrUname');
        }
        return $qb->getQuery()->getOneOrNullResult();
    }
}
