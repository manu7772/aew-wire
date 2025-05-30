<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireAddresslink;
use Aequation\WireBundle\Repository\interface\WireAddresslinkRepositoryInterface;

abstract class WireAddresslinkRepository extends WireRelinkRepository implements WireAddresslinkRepositoryInterface
{

    const NAME = WireAddresslink::class;
    const ALIAS = 'wireaddresslink';

}