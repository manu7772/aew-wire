<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireUrlink;
use Aequation\WireBundle\Repository\interface\WireUrlinkRepositoryInterface;

abstract class WireUrlinkRepository extends WireRelinkRepository implements WireUrlinkRepositoryInterface
{

    public const NAME = WireUrlink::class;
    public const ALIAS = 'wireurlink';

}