<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Repository\interface\GedmoOvrSortableRepositoryInterface;
use Aequation\WireBundle\Repository\interface\WireRelinkRepositoryInterface;
use Aequation\WireBundle\Repository\trait\BaseGedmoOvrSortableRepository;
use Aequation\WireBundle\Repository\trait\BaseTraitWireRepository;
// Symfony
use Gedmo\Sortable\Entity\Repository\SortableRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends SortableRepository
 */
#[AsAlias(WireRelinkRepositoryInterface::class, public: true)]
class WireRelinkRepository extends BaseWireRepository implements WireRelinkRepositoryInterface, GedmoOvrSortableRepositoryInterface
{
    use BaseTraitWireRepository;
    use BaseGedmoOvrSortableRepository;

    const ENTITY_CLASS = WireRelink::class;
    const NAME = 'wire_Relink';

    // public function __construct(
    //     ManagerRegistry $registry,
    //     protected AppWireServiceInterface $appWire,
    // )
    // {
    //     parent::__construct($registry, $appWire);
    // }

    public static function getDefaultAlias(): string
    {
        return static::NAME;
    }

}
