<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireRsoclink;
use Aequation\WireBundle\Repository\interface\WireRsoclinkRepositoryInterface;

abstract class WireRsoclinkRepository extends WireRelinkRepository implements WireRsoclinkRepositoryInterface
{
    const NAME = WireRsoclink::class;
    const ALIAS = 'wiresoclink';
}