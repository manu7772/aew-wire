<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Repository\interface\GedmoOvrSortableRepositoryInterface;
use Aequation\WireBundle\Repository\interface\WireItemRepositoryInterface;
use Aequation\WireBundle\Repository\trait\BaseGedmoOvrSortableRepository;
use Aequation\WireBundle\Repository\trait\BaseTraitWireRepository;
use Doctrine\ORM\Query;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Gedmo\Sortable\Entity\Repository\GedmoOvrSortableRepository;

/**
 * @extends GedmoOvrSortableRepository
 */
#[AsAlias(WireItemRepositoryInterface::class, public: true)]
class WireItemRepository extends BaseWireRepository implements WireItemRepositoryInterface, GedmoOvrSortableRepositoryInterface
{
    use BaseTraitWireRepository;
    use BaseGedmoOvrSortableRepository;

    const NAME = WireItem::class;
    const ALIAS = 'wireitem';

    // public function __construct(
    //     ManagerRegistry $registry,
    //     protected AppWireServiceInterface $appWire,
    // )
    // {
    //     parent::__construct($registry, $appWire);
    // }


}
