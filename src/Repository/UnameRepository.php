<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Repository\interface\UnameRepositoryInterface;
use Aequation\WireBundle\Tools\Encoders;

class UnameRepository extends BaseWireRepository implements UnameRepositoryInterface
{

    const ENTITY_CLASS = Uname::class;
    const NAME = 'uname';

    public function getClassnameByUname(
        string $uname
    ): ?string {
        $qb = $this->createQueryBuilder(static::NAME);
        $qb->select(static::NAME.'.entityEuid')
            ->where(static::NAME.'.id = :uname')
            ->setParameter('uname', $uname);
        $result = $qb->getQuery()->getOneOrNullResult();
        if($euid = $result['entityEuid'] ?? null) {
            return Encoders::getClassOfEuid($euid);
        }
        return null;
    }

}