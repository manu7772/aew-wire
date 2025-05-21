<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireLanguage;
use Aequation\WireBundle\Repository\BaseWireRepository;
use Aequation\WireBundle\Repository\interface\WireLanguageRepositoryInterface;
use Aequation\WireBundle\Repository\trait\BaseGedmoOvrSortableRepository;
use Aequation\WireBundle\Repository\trait\BaseTraitWireRepository;
// Symfony
// use Gedmo\Sortable\Entity\Repository\SortableRepository;

/**
 * @extends BaseWireRepository
 */
abstract class WireLanguageRepository extends BaseWireRepository implements WireLanguageRepositoryInterface
{
    use BaseTraitWireRepository;
    use BaseGedmoOvrSortableRepository;

    const NAME = WireLanguage::class;
    const ALIAS = 'wirelanguage';

    public static function getDefaultAlias(): string
    {
        return static::ALIAS;
    }

}
