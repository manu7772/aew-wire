<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Repository\interface\UnameRepositoryInterface;
use Aequation\WireBundle\Tools\Encoders;

class UnameRepository extends BaseWireRepository implements UnameRepositoryInterface
{

    const NAME = Uname::class;
    const ALIAS = 'uname';

    public function getClassnameByUname(
        string $uname
    ): ?string {
        $qb = $this->createQueryBuilder(static::ALIAS);
        $qb->select(static::ALIAS.'.entityEuid')
            ->where(static::ALIAS.'.id = :uname')
            ->setParameter('uname', $uname);
        $result = $qb->getQuery()->getOneOrNullResult();
        if($euid = $result['entityEuid'] ?? null) {
            return Encoders::getClassOfEuid($euid);
        }
        return null;
    }

}