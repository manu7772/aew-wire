<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireRsoclink;
use Aequation\WireBundle\Repository\interface\WireRsoclinkRepositoryInterface;

abstract class WireRsoclinkRepository extends WireRelinkRepository implements WireRsoclinkRepositoryInterface
{

    public const NAME = WireRsoclink::class;
    public const ALIAS = 'WireRsoclink';

}