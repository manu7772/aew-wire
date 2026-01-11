<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireUrlink;
use Aequation\WireBundle\Repository\interface\WireUrlinkRepositoryInterface;

abstract class WireUrlinkRepository extends WireRelinkRepository implements WireUrlinkRepositoryInterface
{
    const NAME = WireUrlink::class;
    const ALIAS = 'wireurlink';
}