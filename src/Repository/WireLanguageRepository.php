<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireLanguage;
use Aequation\WireBundle\Repository\BaseWireRepository;
use Aequation\WireBundle\Repository\interface\WireLanguageRepositoryInterface;

/**
 * @extends BaseWireRepository
 */
abstract class WireLanguageRepository extends BaseWireRepository implements WireLanguageRepositoryInterface
{

    const ALIAS = 'wirelanguage';
    const ENTITY_CLASS = WireLanguage::class;

}
