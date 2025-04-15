<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireRslink;
use Aequation\WireBundle\Repository\interface\WireRslinkRepositoryInterface;

abstract class WireRslinkRepository extends WireRelinkRepository implements WireRslinkRepositoryInterface
{

    public const NAME = WireRslink::class;
    public const ALIAS = 'wirerslink';

}