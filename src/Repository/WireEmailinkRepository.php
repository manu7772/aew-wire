<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\WireEmailink;
use Aequation\WireBundle\Repository\interface\WireEmailinkRepositoryInterface;

abstract class WireEmailinkRepository extends WireRelinkRepository implements WireEmailinkRepositoryInterface
{

    const NAME = WireEmailink::class;
    const ALIAS = 'wireemailink';

}