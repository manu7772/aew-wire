<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Repository\trait\BaseTraitWireRepository;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Repository\interface\GedmoOvrSortableRepositoryInterface;
// Symfony
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
// PHP
use Exception;

abstract class BaseWireRepository extends ServiceEntityRepository implements BaseWireRepositoryInterface
{
    use BaseTraitWireRepository;

    const NAME = BaseEntityInterface::class;
    const ALIAS = 'u';

    public function __construct(
        ManagerRegistry $registry,
        protected AppWireServiceInterface $appWire,
    )
    {
        parent::__construct(registry: $registry, entityClass: static::NAME);
        if($this instanceof GedmoOvrSortableRepositoryInterface) {
            // instance of Aequation\WireBundle\Repository\interface\GedmoOvrSortableRepositoryInterface
            $this->__self_construct($registry->getManager());
        }
        if($this->appWire->isDev()) {
            if(!is_a($this->getEntityName(), static::NAME, true)) throw new Exception(vsprintf('Error %s line %d: in %s, entity classes %s and %s do not match!', [__METHOD__, __LINE__, __CLASS__, $this->getEntityName(), static::NAME]));
        }
    }

    public static function getDefaultAlias(): string
    {
        return static::ALIAS;
    }


}