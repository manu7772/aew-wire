<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Repository\interface\UnameRepositoryInterface;

class UnameRepository extends BaseWireRepository implements UnameRepositoryInterface
{

    const ENTITY_CLASS = Uname::class;
    const NAME = 'uname';

}