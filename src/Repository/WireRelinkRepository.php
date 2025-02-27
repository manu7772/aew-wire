<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Repository\interface\WireRelinkRepositoryInterface;
use Aequation\WireBundle\Repository\trait\BaseTraitWireRepository;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

/**
 * @extends SortableRepository
 */
abstract class WireRelinkRepository extends SortableRepository implements WireRelinkRepositoryInterface
{
    use BaseTraitWireRepository;

    const ENTITY_CLASS = WireRelink::class;
    const NAME = 'wire_Relink';

    public function __construct(
        EntityManagerInterface $em,
        protected AppWireServiceInterface $appWire,
    )
    {
        parent::__construct(em: $em, class: $em->getClassMetadata(static::ENTITY_CLASS));
        if($this->appWire->isDev()) {
            if(!is_a($this->getEntityName(), static::ENTITY_CLASS, true)) throw new Exception(vsprintf('Error %s line %d: in %s, entity classes %s and %s do not match!', [__METHOD__, __LINE__, __CLASS__, $this->getEntityName(), static::ENTITY_CLASS]));
        }
    }

    public static function getDefaultAlias(): string
    {
        return static::NAME;
    }

}
