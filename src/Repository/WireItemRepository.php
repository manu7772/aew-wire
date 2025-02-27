<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Repository\interface\WireItemRepositoryInterface;
use Aequation\WireBundle\Repository\trait\BaseTraitWireRepository;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// Symfony
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Gedmo\Sortable\Entity\Repository\SortableRepository;
// PHP
use Exception;

/**
 * @extends SortableRepository
 */
#[AsAlias(WireItemRepositoryInterface::class, public: true)]
class WireItemRepository extends SortableRepository implements WireItemRepositoryInterface
{
    use BaseTraitWireRepository;

    const ENTITY_CLASS = WireItem::class;
    const NAME = 'w_item';

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
